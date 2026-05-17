<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Trainer;
use App\Models\TrainerAdvance;
use App\Models\TrainerHour;
use App\Models\TrainerPayroll;
use App\Support\ControlPanel;
use App\Support\TrainerPayrollCycle;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class TrainerPayrollController extends Controller
{
    public function index(Request $request)
    {
        $branch = $this->currentBranch($request);
        $paymentWeek = ControlPanel::trainerPaymentWeek();
        $currentPeriod = TrainerPayrollCycle::currentPeriod(now(), $paymentWeek);

        $trainers = Trainer::query()
            ->where('branch_id', $branch->id)
            ->orderBy('name')
            ->get();

        $setupError = $this->payrollSetupError();
        $selectedTrainer = $setupError ? null : $this->selectedTrainer($request, $branch);
        $selectedSummary = $setupError ? $this->emptySummary() : $this->selectedTrainerSummary($selectedTrainer, $currentPeriod);
        $paidPayrolls = collect();
        $heldPayrolls = collect();

        if (! $setupError) {
            $this->syncHeldPayrolls($branch, $paymentWeek, $currentPeriod['start']);

            $paidPayrolls = TrainerPayroll::query()
                ->with('trainer')
                ->where('branch_id', $branch->id)
                ->where('status', TrainerPayroll::STATUS_PAID)
                ->whereBetween('paid_at', [
                    $currentPeriod['start']->startOfDay(),
                    $currentPeriod['end']->endOfDay(),
                ])
                ->orderByDesc('paid_at')
                ->orderByDesc('id')
                ->get();
            $heldPayrolls = TrainerPayroll::query()
                ->with('trainer')
                ->where('branch_id', $branch->id)
                ->where('status', TrainerPayroll::STATUS_HELD)
                ->orderByDesc('period_end')
                ->orderByDesc('id')
                ->get();
        }

        return $this->dashboardView($request, 'trainer-payrolls.index', [
            'pageTitle' => 'قبض المدربين',
            'paymentWeek' => $paymentWeek,
            'currentPeriod' => $currentPeriod,
            'setupError' => $setupError,
            'trainers' => $trainers,
            'selectedTrainer' => $selectedTrainer,
            'selectedSummary' => $selectedSummary,
            'paidPayrolls' => $paidPayrolls,
            'heldPayrolls' => $heldPayrolls,
            'paidPayrollCount' => $paidPayrolls->count(),
            'heldPayrollCount' => $heldPayrolls->count(),
            'paidTotalAmount' => $paidPayrolls->sum(fn (TrainerPayroll $trainerPayroll) => (float) $trainerPayroll->total_amount),
            'heldTotalAmount' => $heldPayrolls->sum(fn (TrainerPayroll $trainerPayroll) => (float) $trainerPayroll->total_amount),
        ], 'trainer-payrolls');
    }

    public function store(Request $request): RedirectResponse
    {
        if ($setupError = $this->payrollSetupError()) {
            return back()->withErrors(['trainer_id' => $setupError]);
        }

        $branch = $this->currentBranch($request);
        $paymentWeek = ControlPanel::trainerPaymentWeek();
        $currentPeriod = TrainerPayrollCycle::currentPeriod(now(), $paymentWeek);

        $this->syncHeldPayrolls($branch, $paymentWeek, $currentPeriod['start']);

        $trainer = $this->validatedTrainer($request, $branch);
        $summary = $this->selectedTrainerSummary($trainer, $currentPeriod);

        if ($summary['net_amount'] <= 0 || $summary['has_current_payroll']) {
            return redirect()
                ->route('trainer-payrolls.index', ['trainer_id' => $trainer->id])
                ->withErrors(['trainer_id' => 'لا يوجد راتب متاح للصرف']);
        }

        TrainerPayroll::query()->create([
            'branch_id' => $branch->id,
            'trainer_id' => $trainer->id,
            'period_start' => $currentPeriod['start']->toDateString(),
            'period_end' => $currentPeriod['end']->toDateString(),
            'hours' => $summary['hours'],
            'hourly_rate' => $summary['hourly_rate'],
            'total_amount' => $summary['total_amount'],
            'advance_amount' => $summary['advance_amount'],
            'net_amount' => $summary['net_amount'],
            'status' => TrainerPayroll::STATUS_PAID,
            'paid_at' => now(),
        ]);

        return redirect()
            ->route('trainer-payrolls.index', ['trainer_id' => $trainer->id])
            ->with('status', 'تم صرف راتب المدرب');
    }

    public function release(Request $request, string $trainerPayroll): RedirectResponse
    {
        if ($setupError = $this->payrollSetupError()) {
            return back()->withErrors(['trainer_id' => $setupError]);
        }

        $branch = $this->currentBranch($request);
        $trainerPayroll = TrainerPayroll::query()
            ->whereKey($trainerPayroll)
            ->where('branch_id', $branch->id)
            ->where('status', TrainerPayroll::STATUS_HELD)
            ->firstOrFail();

        $trainerPayroll->update([
            'status' => TrainerPayroll::STATUS_PAID,
            'paid_at' => now(),
        ]);

        return redirect()
            ->route('trainer-payrolls.index', ['trainer_id' => $trainerPayroll->trainer_id])
            ->with('status', 'تم صرف الراتب المحجوز');
    }

    protected function currentBranch(Request $request): Branch
    {
        $branch = ControlPanel::currentBranch($request->user());
        abort_unless($branch, 403);

        return $branch;
    }

    protected function selectedTrainer(Request $request, Branch $branch): ?Trainer
    {
        $data = validator($request->all(), [
            'trainer_id' => [
                'nullable',
                'integer',
                Rule::exists('trainers', 'id')->where(fn ($builder) => $builder->where('branch_id', $branch->id)),
            ],
        ])->validate();

        if (blank($data['trainer_id'] ?? null)) {
            return null;
        }

        return Trainer::query()
            ->where('branch_id', $branch->id)
            ->find($data['trainer_id']);
    }

    protected function validatedTrainer(Request $request, Branch $branch): Trainer
    {
        $data = $request->validate([
            'trainer_id' => [
                'required',
                'integer',
                Rule::exists('trainers', 'id')->where(fn ($builder) => $builder->where('branch_id', $branch->id)),
            ],
        ], [
            'trainer_id.required' => 'المدرب مطلوب',
            'trainer_id.exists' => 'المدرب غير متاح في هذا الفرع',
        ]);

        return Trainer::query()
            ->where('branch_id', $branch->id)
            ->findOrFail($data['trainer_id']);
    }

    protected function selectedTrainerSummary(?Trainer $trainer, array $currentPeriod): array
    {
        if (! $trainer) {
            return $this->emptySummary();
        }

        $currentPayroll = TrainerPayroll::query()
            ->where('trainer_id', $trainer->id)
            ->whereDate('period_start', $currentPeriod['start']->toDateString())
            ->whereDate('period_end', $currentPeriod['end']->toDateString())
            ->first();

        if ($currentPayroll) {
            return [
                'hours' => (float) $currentPayroll->hours,
                'hourly_rate' => (float) $currentPayroll->hourly_rate,
                'total_amount' => (float) $currentPayroll->total_amount,
                'advance_amount' => (float) $currentPayroll->advance_amount,
                'net_amount' => (float) $currentPayroll->net_amount,
                'has_current_payroll' => true,
            ];
        }

        $hours = (float) $trainer->trainerHours()
            ->whereBetween('worked_on', [
                $currentPeriod['start']->toDateString(),
                $currentPeriod['end']->toDateString(),
            ])
            ->sum('hours');
        $advanceAmount = (float) $trainer->trainerAdvances()
            ->whereBetween('advanced_on', [
                $currentPeriod['start']->toDateString(),
                $currentPeriod['end']->toDateString(),
            ])
            ->sum('amount');
        $hourlyRate = (float) $trainer->hourly_rate;
        $totalAmount = $hours * $hourlyRate;

        return [
            'hours' => $hours,
            'hourly_rate' => $hourlyRate,
            'total_amount' => $totalAmount,
            'advance_amount' => $advanceAmount,
            'net_amount' => $totalAmount - $advanceAmount,
            'has_current_payroll' => false,
        ];
    }

    protected function emptySummary(): array
    {
        return [
            'hours' => 0,
            'hourly_rate' => 0,
            'total_amount' => 0,
            'advance_amount' => 0,
            'net_amount' => 0,
            'has_current_payroll' => false,
        ];
    }

    protected function syncHeldPayrolls(Branch $branch, array $paymentWeek, CarbonImmutable $currentPeriodStart): void
    {
        $earliestWorkedOn = TrainerHour::query()
            ->whereHas('trainer', fn ($builder) => $builder->where('branch_id', $branch->id))
            ->min('worked_on');
        $earliestAdvancedOn = TrainerAdvance::query()
            ->whereHas('trainer', fn ($builder) => $builder->where('branch_id', $branch->id))
            ->min('advanced_on');
        $earliestActivity = collect([$earliestWorkedOn, $earliestAdvancedOn])
            ->filter()
            ->sort()
            ->first();

        if (! $earliestActivity) {
            return;
        }

        $cursor = TrainerPayrollCycle::periodStartForReference(
            CarbonImmutable::parse($earliestActivity),
            $paymentWeek,
        );

        while ($cursor->lt($currentPeriodStart)) {
            $period = TrainerPayrollCycle::periodFromStart($cursor, $paymentWeek);

            if ($period['end']->gte($currentPeriodStart)) {
                break;
            }

            $this->createHeldPayrollsForPeriod($branch, $period);
            $cursor = $cursor->addWeek();
        }
    }

    protected function createHeldPayrollsForPeriod(Branch $branch, array $period): void
    {
        $startDate = $period['start']->toDateString();
        $endDate = $period['end']->toDateString();

        Trainer::query()
            ->where('branch_id', $branch->id)
            ->withSum([
                'trainerHours as period_hours' => fn ($builder) => $builder->whereBetween('worked_on', [$startDate, $endDate]),
            ], 'hours')
            ->withSum([
                'trainerAdvances as period_advances' => fn ($builder) => $builder->whereBetween('advanced_on', [$startDate, $endDate]),
            ], 'amount')
            ->get()
            ->each(function (Trainer $trainer) use ($branch, $startDate, $endDate): void {
                $hours = (float) ($trainer->period_hours ?? 0);
                $advanceAmount = (float) ($trainer->period_advances ?? 0);

                if ($hours <= 0 && $advanceAmount <= 0) {
                    return;
                }

                $alreadyExists = TrainerPayroll::query()
                    ->where('trainer_id', $trainer->id)
                    ->whereDate('period_start', $startDate)
                    ->whereDate('period_end', $endDate)
                    ->exists();

                if ($alreadyExists) {
                    return;
                }

                $hourlyRate = (float) $trainer->hourly_rate;
                $totalAmount = $hours * $hourlyRate;

                TrainerPayroll::query()->create([
                    'branch_id' => $branch->id,
                    'trainer_id' => $trainer->id,
                    'period_start' => $startDate,
                    'period_end' => $endDate,
                    'hours' => $hours,
                    'hourly_rate' => $hourlyRate,
                    'total_amount' => $totalAmount,
                    'advance_amount' => $advanceAmount,
                    'net_amount' => $totalAmount - $advanceAmount,
                    'status' => TrainerPayroll::STATUS_HELD,
                    'held_at' => now(),
                ]);
            });
    }

    protected function payrollSetupError(): ?string
    {
        if (! Schema::hasTable('trainer_payrolls')) {
            return 'جدول قبض المدربين غير مهيأ بعد. شغل php artisan migrate ثم أعد تحميل الصفحة.';
        }

        return null;
    }
}
