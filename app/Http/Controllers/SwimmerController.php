<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Swimmer;
use App\Models\TrainingGroup;
use App\Support\ControlPanel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SwimmerController extends Controller
{
    public function index(Request $request)
    {
        return $this->swimmersView($request);
    }

    public function store(Request $request): RedirectResponse
    {
        $currentBranch = ControlPanel::currentBranch($request->user());
        abort_unless($currentBranch, 403);
        $data = $this->validatedPayload($request, $currentBranch);

        Swimmer::query()->create($data);

        return redirect()->route('swimmers.index')->with('status', 'تم إضافة السباح');
    }

    public function edit(Request $request, Swimmer $swimmer)
    {
        $swimmer = $this->scopedSwimmersQuery($request)->findOrFail($swimmer->id);

        return $this->swimmersView($request, $swimmer);
    }

    public function update(Request $request, Swimmer $swimmer): RedirectResponse
    {
        $currentBranch = ControlPanel::currentBranch($request->user());
        abort_unless($currentBranch, 403);
        $swimmer = $this->scopedSwimmersQuery($request)->findOrFail($swimmer->id);
        $data = $this->validatedPayload($request, $currentBranch, $swimmer);

        $swimmer->update($data);

        return redirect()->route('swimmers.index')->with('status', 'تم تحديث بيانات السباح');
    }

    public function destroy(Request $request, Swimmer $swimmer): RedirectResponse
    {
        $swimmer = $this->scopedSwimmersQuery($request)->findOrFail($swimmer->id);
        $swimmer->delete();

        return redirect()->route('swimmers.index')->with('status', 'تم حذف السباح');
    }

    protected function swimmersView(Request $request, ?Swimmer $editedSwimmer = null)
    {
        $trainingGroups = $this->scopedTrainingGroupsQuery($request)->get();
        $swimmers = $this->scopedSwimmersQuery($request)
            ->with(['trainingGroup', 'swimmerFiles'])
            ->get();

        return $this->dashboardView($request, 'swimmers.index', [
            'pageTitle' => 'السباحين',
            'swimmers' => $swimmers,
            'trainingGroups' => $trainingGroups,
            'editedSwimmer' => $editedSwimmer,
            'showCreateForm' => $request->boolean('create') || $request->hasOldInput() || $editedSwimmer !== null,
            'nextSerialNumber' => $editedSwimmer?->serial_number ?? Swimmer::nextSerialNumber(),
            'currentYear' => now()->year,
        ], 'swimmers');
    }

    protected function scopedSwimmersQuery(Request $request): Builder
    {
        $currentBranch = ControlPanel::currentBranch($request->user());
        $query = Swimmer::query()->latest();

        if (! $currentBranch) {
            return $query->whereNull('id');
        }

        return $query->where('branch_id', $currentBranch->id);
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

    protected function validatedPayload(Request $request, Branch $branch, ?Swimmer $swimmer = null): array
    {
        $currentYear = now()->year;
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'birth_year' => ['required', 'integer', 'min:1950', 'max:'.$currentYear],
            'father_phone' => ['required', 'string', 'max:30'],
            'mother_phone' => ['required', 'string', 'max:30'],
            'training_group_id' => [
                'required',
                'integer',
                Rule::exists('training_groups', 'id')->where(fn ($query) => $query->where('branch_id', $branch->id)),
            ],
            'subscription_start_date' => ['required', 'date'],
            'group_price' => ['required', 'numeric', 'min:0'],
            'amount_paid' => ['required', 'numeric', 'min:0'],
        ], [
            'name.required' => 'اسم السباح مطلوب',
            'birth_year.required' => 'سنة الميلاد مطلوبة',
            'birth_year.integer' => 'سنة الميلاد غير صحيحة',
            'birth_year.min' => 'سنة الميلاد غير صحيحة',
            'birth_year.max' => 'سنة الميلاد غير صحيحة',
            'father_phone.required' => 'رقم الأب مطلوب',
            'mother_phone.required' => 'رقم الأم مطلوب',
            'training_group_id.required' => 'المجموعة مطلوبة',
            'training_group_id.exists' => 'المجموعة غير متاحة في الفرع الحالي',
            'subscription_start_date.required' => 'تاريخ بداية الاشتراك مطلوب',
            'group_price.required' => 'سعر المجموعة مطلوب',
            'group_price.numeric' => 'سعر المجموعة غير صحيح',
            'amount_paid.required' => 'المبلغ المدفوع مطلوب',
            'amount_paid.numeric' => 'المبلغ المدفوع غير صحيح',
        ]);

        $trainingGroup = TrainingGroup::query()
            ->where('branch_id', $branch->id)
            ->findOrFail($validated['training_group_id']);

        $serialNumber = $swimmer?->serial_number ?? Swimmer::nextSerialNumber();
        $remainingAmount = round((float) $validated['group_price'] - (float) $validated['amount_paid'], 2);

        return [
            ...$validated,
            'branch_id' => $branch->id,
            'serial_number' => $serialNumber,
            'barcode' => Swimmer::generateBarcode(
                $serialNumber,
                $validated['name'],
                (int) $validated['birth_year'],
                $validated['father_phone'],
                $validated['mother_phone'],
                $trainingGroup->name,
            ),
            'subscription_end_date' => Swimmer::calculateSubscriptionEndDate($validated['subscription_start_date'], $trainingGroup),
            'remaining_amount' => $remainingAmount,
        ];
    }
}
