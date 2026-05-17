<?php

namespace App\Http\Controllers;

use App\Models\Administrator;
use App\Models\AdministratorPayroll;
use App\Models\Branch;
use App\Support\ControlPanel;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AdministratorPayrollController extends Controller
{
    public function index(Request $request)
    {
        $branch = $this->currentBranch($request);
        $currentMonth = $this->currentMonth();
        $setupError = $this->payrollSetupError();
        $availableAdministrators = $setupError
            ? collect()
            : $this->availableAdministrators($branch, $currentMonth);
        $selectedAdministrator = $setupError
            ? null
            : $this->selectedAdministrator($request, $branch, $availableAdministrators);
        $paidPayrolls = $setupError
            ? collect()
            : AdministratorPayroll::query()
                ->with('administrator')
                ->where('branch_id', $branch->id)
                ->orderByDesc('paid_at')
                ->orderByDesc('id')
                ->get();

        return $this->dashboardView($request, 'administrator-payrolls.index', [
            'pageTitle' => 'قبض الإداريين',
            'setupError' => $setupError,
            'currentMonth' => $currentMonth,
            'availableAdministrators' => $availableAdministrators,
            'selectedAdministrator' => $selectedAdministrator,
            'paidPayrolls' => $paidPayrolls,
            'paidPayrollCount' => $paidPayrolls->count(),
            'paidTotalAmount' => $paidPayrolls->sum(fn (AdministratorPayroll $administratorPayroll) => (float) $administratorPayroll->amount),
        ], 'administrator-payrolls');
    }

    public function store(Request $request): RedirectResponse
    {
        if ($setupError = $this->payrollSetupError()) {
            return back()->withErrors(['administrator_id' => $setupError]);
        }

        $branch = $this->currentBranch($request);
        $currentMonth = $this->currentMonth();
        $administrator = $this->validatedAdministrator($request, $branch, $currentMonth);

        AdministratorPayroll::query()->create([
            'branch_id' => $branch->id,
            'administrator_id' => $administrator->id,
            'period_start' => $currentMonth['start']->toDateString(),
            'period_end' => $currentMonth['end']->toDateString(),
            'amount' => $administrator->salary,
            'paid_at' => now(),
        ]);

        return redirect()
            ->route('administrator-payrolls.index')
            ->with('status', 'تم صرف راتب الإداري');
    }

    protected function currentBranch(Request $request): Branch
    {
        $branch = ControlPanel::currentBranch($request->user());
        abort_unless($branch, 403);

        return $branch;
    }

    protected function currentMonth(): array
    {
        $reference = CarbonImmutable::parse(now()->toDateString());

        return [
            'start' => $reference->startOfMonth(),
            'end' => $reference->endOfMonth(),
        ];
    }

    protected function availableAdministrators(Branch $branch, array $currentMonth)
    {
        return Administrator::query()
            ->where('branch_id', $branch->id)
            ->whereDoesntHave('administratorPayrolls', function ($builder) use ($currentMonth): void {
                $builder
                    ->whereDate('period_start', $currentMonth['start']->toDateString())
                    ->whereDate('period_end', $currentMonth['end']->toDateString());
            })
            ->orderBy('name')
            ->get();
    }

    protected function selectedAdministrator(Request $request, Branch $branch, $availableAdministrators): ?Administrator
    {
        $data = validator($request->all(), [
            'administrator_id' => [
                'nullable',
                'integer',
                Rule::exists('administrators', 'id')->where(fn ($builder) => $builder->where('branch_id', $branch->id)),
            ],
        ])->validate();

        if (blank($data['administrator_id'] ?? null)) {
            return null;
        }

        return $availableAdministrators->firstWhere('id', (int) $data['administrator_id']);
    }

    protected function validatedAdministrator(Request $request, Branch $branch, array $currentMonth): Administrator
    {
        $data = $request->validate([
            'administrator_id' => [
                'required',
                'integer',
                Rule::exists('administrators', 'id')->where(fn ($builder) => $builder->where('branch_id', $branch->id)),
            ],
        ], [
            'administrator_id.required' => 'الإداري مطلوب',
            'administrator_id.exists' => 'الإداري غير متاح في هذا الفرع',
        ]);

        $administrator = Administrator::query()
            ->where('branch_id', $branch->id)
            ->findOrFail($data['administrator_id']);

        $alreadyPaid = AdministratorPayroll::query()
            ->where('administrator_id', $administrator->id)
            ->whereDate('period_start', $currentMonth['start']->toDateString())
            ->whereDate('period_end', $currentMonth['end']->toDateString())
            ->exists();

        if ($alreadyPaid) {
            throw ValidationException::withMessages([
                'administrator_id' => 'تم صرف راتب هذا الإداري بالفعل',
            ]);
        }

        return $administrator;
    }

    protected function payrollSetupError(): ?string
    {
        if (! Schema::hasTable('administrator_payrolls')) {
            return 'جدول قبض الإداريين غير مهيأ بعد. شغل php artisan migrate ثم أعد تحميل الصفحة.';
        }

        return null;
    }
}
