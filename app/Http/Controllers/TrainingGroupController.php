<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Trainer;
use App\Models\TrainingGroup;
use App\Support\ControlPanel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class TrainingGroupController extends Controller
{
    public function index(Request $request)
    {
        return $this->trainingGroupsView($request);
    }

    public function store(Request $request): RedirectResponse
    {
        $currentBranch = ControlPanel::currentBranch($request->user());
        abort_unless($currentBranch, 403);
        $data = $this->validatedPayload($request, $currentBranch);

        TrainingGroup::query()->create($data);

        return redirect()->route('training-groups.index')->with('status', 'تم إضافة المجموعة');
    }

    public function edit(Request $request, TrainingGroup $trainingGroup)
    {
        $trainingGroup = $this->scopedTrainingGroupsQuery($request)->findOrFail($trainingGroup->id);

        return $this->trainingGroupsView($request, $trainingGroup);
    }

    public function update(Request $request, TrainingGroup $trainingGroup): RedirectResponse
    {
        $currentBranch = ControlPanel::currentBranch($request->user());
        abort_unless($currentBranch, 403);
        $trainingGroup = $this->scopedTrainingGroupsQuery($request)->findOrFail($trainingGroup->id);
        $data = $this->validatedPayload($request, $currentBranch);

        $trainingGroup->update($data);

        return redirect()->route('training-groups.index')->with('status', 'تم تحديث المجموعة');
    }

    public function destroy(Request $request, TrainingGroup $trainingGroup): RedirectResponse
    {
        $trainingGroup = $this->scopedTrainingGroupsQuery($request)->findOrFail($trainingGroup->id);
        $trainingGroup->delete();

        return redirect()->route('training-groups.index')->with('status', 'تم حذف المجموعة');
    }

    protected function trainingGroupsView(Request $request, ?TrainingGroup $editedTrainingGroup = null)
    {
        $trainers = $this->scopedTrainersQuery($request)->get();
        $trainingGroups = $this->scopedTrainingGroupsQuery($request)
            ->with('trainer')
            ->get();

        return $this->dashboardView($request, 'training-groups.index', [
            'pageTitle' => 'المجموعات',
            'trainingGroups' => $trainingGroups,
            'editedTrainingGroup' => $editedTrainingGroup,
            'trainers' => $trainers,
            'groupLevels' => TrainingGroup::levelOptions(),
            'weekDays' => TrainingGroup::weekDayOptions(),
        ], 'training-groups');
    }

    protected function scopedTrainingGroupsQuery(Request $request): Builder
    {
        $currentBranch = ControlPanel::currentBranch($request->user());
        $query = TrainingGroup::query()->orderBy('name');

        if (! $currentBranch) {
            return $query->whereNull('id');
        }

        return $query->where('branch_id', $currentBranch->id);
    }

    protected function scopedTrainersQuery(Request $request): Builder
    {
        $currentBranch = ControlPanel::currentBranch($request->user());
        $query = Trainer::query()->orderBy('name');

        if (! $currentBranch) {
            return $query->whereNull('id');
        }

        return $query->where('branch_id', $currentBranch->id);
    }

    protected function validatedPayload(Request $request, Branch $branch): array
    {
        $validator = Validator::make($request->all(), [
            'level' => ['required', Rule::in(TrainingGroup::levelOptions())],
            'trainer_id' => [
                'required',
                'integer',
                Rule::exists('trainers', 'id')->where(fn ($query) => $query->where('branch_id', $branch->id)),
            ],
            'training_days_per_week' => ['required', 'integer', 'min:1', 'max:7'],
            'available_training_days' => ['required', 'integer', 'min:1', 'max:7', 'gte:training_days_per_week'],
            'max_swimmers' => ['required', 'integer', 'min:1'],
            'price' => ['required', 'numeric', 'min:0'],
            'schedule' => ['required', 'array', 'min:1', 'max:7'],
            'schedule.*.day' => ['required', Rule::in(array_keys(TrainingGroup::weekDayOptions()))],
            'schedule.*.time' => ['required', 'date_format:H:i'],
        ], [
            'level.required' => 'مستوى المجموعة مطلوب',
            'level.in' => 'مستوى المجموعة غير صحيح',
            'trainer_id.required' => 'المدرب مطلوب',
            'trainer_id.exists' => 'المدرب غير متاح في الفرع الحالي',
            'training_days_per_week.required' => 'عدد أيام التمرين مطلوب',
            'training_days_per_week.integer' => 'عدد أيام التمرين غير صحيح',
            'training_days_per_week.min' => 'عدد أيام التمرين يجب أن يكون يومًا واحدًا على الأقل',
            'training_days_per_week.max' => 'عدد أيام التمرين لا يمكن أن يزيد عن 7',
            'available_training_days.required' => 'عدد الأيام المتاحة مطلوب',
            'available_training_days.integer' => 'عدد الأيام المتاحة غير صحيح',
            'available_training_days.gte' => 'عدد الأيام المتاحة يجب أن يكون مساويًا أو أكبر من عدد أيام التمرين',
            'available_training_days.max' => 'عدد الأيام المتاحة لا يمكن أن يزيد عن 7',
            'max_swimmers.required' => 'الحد الأقصى للسباحين مطلوب',
            'max_swimmers.integer' => 'الحد الأقصى للسباحين غير صحيح',
            'max_swimmers.min' => 'الحد الأقصى للسباحين يجب أن يكون 1 على الأقل',
            'price.required' => 'سعر المجموعة مطلوب',
            'price.numeric' => 'سعر المجموعة غير صحيح',
            'price.min' => 'سعر المجموعة يجب أن يكون صفرًا أو أكثر',
            'schedule.required' => 'مواعيد التمرين مطلوبة',
            'schedule.array' => 'مواعيد التمرين غير صحيحة',
            'schedule.*.day.required' => 'يوم التمرين مطلوب',
            'schedule.*.day.in' => 'يوم التمرين غير صحيح',
            'schedule.*.time.required' => 'ساعة التمرين مطلوبة',
            'schedule.*.time.date_format' => 'ساعة التمرين يجب أن تكون بنظام 24 ساعة',
        ]);

        $validator->after(function ($validator) use ($request): void {
            $schedule = $this->normalizedSchedule($request->input('schedule', []));
            $trainingDaysPerWeek = (int) $request->input('training_days_per_week', 0);

            if (count($schedule) !== $trainingDaysPerWeek) {
                $validator->errors()->add('schedule', 'يجب تحديد نفس عدد أيام التمرين المختار.');
            }

            if (collect($schedule)->pluck('day')->unique()->count() !== count($schedule)) {
                $validator->errors()->add('schedule', 'لا يمكن تكرار نفس اليوم أكثر من مرة.');
            }
        });

        $validated = $validator->validate();
        $validated['schedule'] = $this->normalizedSchedule($validated['schedule']);

        $trainer = Trainer::query()
            ->where('branch_id', $branch->id)
            ->findOrFail($validated['trainer_id']);

        $validated['name'] = TrainingGroup::generateName(
            $validated['level'],
            $trainer->name,
            $validated['schedule'],
        );

        return $validated + ['branch_id' => $branch->id];
    }

    protected function normalizedSchedule(array $schedule): array
    {
        return Collection::make($schedule)
            ->map(function ($entry): ?array {
                $day = is_array($entry) ? ($entry['day'] ?? null) : null;
                $time = is_array($entry) ? ($entry['time'] ?? null) : null;

                if (blank($day) && blank($time)) {
                    return null;
                }

                return [
                    'day' => $day,
                    'time' => filled($time) ? mb_substr((string) $time, 0, 5) : $time,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }
}
