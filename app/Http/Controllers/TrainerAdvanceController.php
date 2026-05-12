<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Trainer;
use App\Models\TrainerAdvance;
use App\Support\ControlPanel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class TrainerAdvanceController extends Controller
{
    public function index(Request $request)
    {
        return $this->trainerAdvancesView($request);
    }

    public function store(Request $request): RedirectResponse
    {
        $branch = $this->currentBranch($request);
        $data = $this->validatedPayload($request, $branch);

        TrainerAdvance::query()->create($data);

        return redirect()
            ->route('trainer-advances.index', ['date' => $data['advanced_on']])
            ->with('status', 'تم إضافة سلفة المدرب');
    }

    public function edit(Request $request, TrainerAdvance $trainerAdvance)
    {
        $trainerAdvance = $this->scopedTrainerAdvancesQuery($request)->findOrFail($trainerAdvance->id);

        return $this->trainerAdvancesView($request, $trainerAdvance);
    }

    public function update(Request $request, TrainerAdvance $trainerAdvance): RedirectResponse
    {
        $branch = $this->currentBranch($request);
        $trainerAdvance = $this->scopedTrainerAdvancesQuery($request)->findOrFail($trainerAdvance->id);
        $data = $this->validatedPayload($request, $branch);

        $trainerAdvance->update($data);

        return redirect()
            ->route('trainer-advances.index', ['date' => $data['advanced_on']])
            ->with('status', 'تم تحديث سلفة المدرب');
    }

    public function destroy(Request $request, TrainerAdvance $trainerAdvance): RedirectResponse
    {
        $trainerAdvance = $this->scopedTrainerAdvancesQuery($request)->findOrFail($trainerAdvance->id);
        $selectedDate = $this->selectedDate($request, $trainerAdvance->advanced_on?->toDateString());

        $trainerAdvance->delete();

        return redirect()
            ->route('trainer-advances.index', ['date' => $selectedDate])
            ->with('status', 'تم حذف سلفة المدرب');
    }

    protected function trainerAdvancesView(Request $request, ?TrainerAdvance $editedTrainerAdvance = null)
    {
        $branch = $this->currentBranch($request);
        $selectedDate = $this->selectedDate($request, $editedTrainerAdvance?->advanced_on?->toDateString());
        $trainers = Trainer::query()
            ->where('branch_id', $branch->id)
            ->orderBy('name')
            ->get();
        $trainerAdvances = $this->scopedTrainerAdvancesQuery($request)
            ->whereDate('advanced_on', $selectedDate)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        return $this->dashboardView($request, 'trainer-advances.index', [
            'pageTitle' => 'سلف المدربين',
            'activeDate' => $selectedDate,
            'trainers' => $trainers,
            'trainerAdvances' => $trainerAdvances,
            'editedTrainerAdvance' => $editedTrainerAdvance,
            'advanceCount' => $trainerAdvances->count(),
            'trainerCount' => $trainerAdvances->pluck('trainer_id')->unique()->count(),
            'totalAmount' => $trainerAdvances->sum(fn (TrainerAdvance $trainerAdvance) => (float) $trainerAdvance->amount),
        ], 'trainer-advances');
    }

    protected function currentBranch(Request $request): Branch
    {
        $branch = ControlPanel::currentBranch($request->user());
        abort_unless($branch, 403);

        return $branch;
    }

    protected function scopedTrainerAdvancesQuery(Request $request): Builder
    {
        $branch = $this->currentBranch($request);

        return TrainerAdvance::query()
            ->with('trainer')
            ->whereHas('trainer', fn (Builder $builder) => $builder->where('branch_id', $branch->id));
    }

    protected function selectedDate(Request $request, ?string $fallback = null): string
    {
        $data = validator($request->all(), [
            'date' => ['nullable', 'date'],
        ])->validate();

        return Carbon::parse($data['date'] ?? $fallback ?? now()->toDateString())->toDateString();
    }

    protected function validatedPayload(Request $request, Branch $branch): array
    {
        return $request->validate([
            'trainer_id' => [
                'required',
                'integer',
                Rule::exists('trainers', 'id')->where(fn ($builder) => $builder->where('branch_id', $branch->id)),
            ],
            'advanced_on' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'gt:0'],
        ], [
            'trainer_id.required' => 'المدرب مطلوب',
            'trainer_id.exists' => 'المدرب غير متاح في هذا الفرع',
            'advanced_on.required' => 'التاريخ مطلوب',
            'advanced_on.date' => 'التاريخ غير صحيح',
            'amount.required' => 'المبلغ مطلوب',
            'amount.numeric' => 'المبلغ غير صحيح',
            'amount.gt' => 'المبلغ يجب أن يكون أكبر من صفر',
        ]);
    }
}
