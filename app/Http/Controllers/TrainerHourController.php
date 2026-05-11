<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Trainer;
use App\Models\TrainerHour;
use App\Support\ControlPanel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class TrainerHourController extends Controller
{
    public function index(Request $request)
    {
        return $this->trainerHoursView($request);
    }

    public function store(Request $request): RedirectResponse
    {
        $branch = $this->currentBranch($request);
        $data = $this->validatedPayload($request, $branch);

        TrainerHour::query()->create($data);

        return redirect()
            ->route('trainer-hours.index', ['date' => $data['worked_on']])
            ->with('status', 'تم إضافة ساعات المدرب');
    }

    public function edit(Request $request, TrainerHour $trainerHour)
    {
        $trainerHour = $this->scopedTrainerHoursQuery($request)->findOrFail($trainerHour->id);

        return $this->trainerHoursView($request, $trainerHour);
    }

    public function update(Request $request, TrainerHour $trainerHour): RedirectResponse
    {
        $branch = $this->currentBranch($request);
        $trainerHour = $this->scopedTrainerHoursQuery($request)->findOrFail($trainerHour->id);
        $data = $this->validatedPayload($request, $branch, $trainerHour);

        $trainerHour->update($data);

        return redirect()
            ->route('trainer-hours.index', ['date' => $data['worked_on']])
            ->with('status', 'تم تحديث ساعات المدرب');
    }

    public function destroy(Request $request, TrainerHour $trainerHour): RedirectResponse
    {
        $trainerHour = $this->scopedTrainerHoursQuery($request)->findOrFail($trainerHour->id);
        $selectedDate = $this->selectedDate($request, $trainerHour->worked_on?->toDateString());

        $trainerHour->delete();

        return redirect()
            ->route('trainer-hours.index', ['date' => $selectedDate])
            ->with('status', 'تم حذف ساعات المدرب');
    }

    protected function trainerHoursView(Request $request, ?TrainerHour $editedTrainerHour = null)
    {
        $branch = $this->currentBranch($request);
        $selectedDate = $this->selectedDate($request, $editedTrainerHour?->worked_on?->toDateString());
        $trainers = Trainer::query()
            ->where('branch_id', $branch->id)
            ->orderBy('name')
            ->get();

        $trainerHours = $this->scopedTrainerHoursQuery($request)
            ->whereDate('worked_on', $selectedDate)
            ->get()
            ->sortBy(fn (TrainerHour $trainerHour) => $trainerHour->trainer?->name)
            ->values();

        $absentTrainers = Trainer::query()
            ->where('branch_id', $branch->id)
            ->whereNotIn('id', $trainerHours->pluck('trainer_id'))
            ->orderBy('name')
            ->get();

        $totalHours = $trainerHours->sum(fn (TrainerHour $trainerHour) => (float) $trainerHour->hours);
        $totalPay = $trainerHours->sum(fn (TrainerHour $trainerHour) => (float) $trainerHour->hours * (float) $trainerHour->trainer->hourly_rate);

        return $this->dashboardView($request, 'trainer-hours.index', [
            'pageTitle' => 'ساعات المدربين',
            'activeDate' => $selectedDate,
            'trainers' => $trainers,
            'trainerHours' => $trainerHours,
            'absentTrainers' => $absentTrainers,
            'editedTrainerHour' => $editedTrainerHour,
            'isManager' => $request->user()->isManager(),
            'attendanceCount' => $trainerHours->count(),
            'absenceCount' => $absentTrainers->count(),
            'totalHours' => $totalHours,
            'totalPay' => $totalPay,
        ], 'trainer-hours');
    }

    protected function currentBranch(Request $request): Branch
    {
        $branch = ControlPanel::currentBranch($request->user());
        abort_unless($branch, 403);

        return $branch;
    }

    protected function scopedTrainerHoursQuery(Request $request): Builder
    {
        $branch = $this->currentBranch($request);

        return TrainerHour::query()
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

    protected function validatedPayload(Request $request, Branch $branch, ?TrainerHour $trainerHour = null): array
    {
        return $request->validate([
            'trainer_id' => [
                'required',
                'integer',
                Rule::exists('trainers', 'id')->where(fn ($builder) => $builder->where('branch_id', $branch->id)),
                Rule::unique('trainer_hours', 'trainer_id')
                    ->ignore($trainerHour?->id)
                    ->where(fn ($builder) => $builder->where('worked_on', $request->input('worked_on'))),
            ],
            'worked_on' => ['required', 'date'],
            'hours' => ['required', 'numeric', 'gt:0'],
        ], [
            'trainer_id.required' => 'المدرب مطلوب',
            'trainer_id.exists' => 'المدرب غير متاح في هذا الفرع',
            'trainer_id.unique' => 'تم تسجيل ساعات هذا المدرب في هذا اليوم',
            'worked_on.required' => 'التاريخ مطلوب',
            'worked_on.date' => 'التاريخ غير صحيح',
            'hours.required' => 'عدد الساعات مطلوب',
            'hours.numeric' => 'عدد الساعات غير صحيح',
            'hours.gt' => 'عدد الساعات يجب أن يكون أكبر من صفر',
        ]);
    }
}
