<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Trainer;
use App\Support\ControlPanel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TrainerController extends Controller
{
    public function index(Request $request)
    {
        return $this->trainersView($request);
    }

    public function store(Request $request): RedirectResponse
    {
        $currentBranch = ControlPanel::currentBranch($request->user());
        abort_unless($currentBranch, 403);
        $data = $this->validatedPayload($request, $currentBranch);

        Trainer::query()->create($data);

        return redirect()->route('trainers.index')->with('status', 'تم إضافة المدرب');
    }

    public function edit(Request $request, Trainer $trainer)
    {
        $trainer = $this->scopedTrainersQuery($request)->findOrFail($trainer->id);

        return $this->trainersView($request, $trainer);
    }

    public function update(Request $request, Trainer $trainer): RedirectResponse
    {
        $currentBranch = ControlPanel::currentBranch($request->user());
        abort_unless($currentBranch, 403);
        $trainer = $this->scopedTrainersQuery($request)->findOrFail($trainer->id);
        $data = $this->validatedPayload($request, $currentBranch, $trainer);

        if (blank($data['password'])) {
            unset($data['password']);
        }

        $trainer->update($data);

        return redirect()->route('trainers.index')->with('status', 'تم تحديث المدرب');
    }

    public function destroy(Request $request, Trainer $trainer): RedirectResponse
    {
        $trainer = $this->scopedTrainersQuery($request)->findOrFail($trainer->id);
        $trainer->delete();

        return redirect()->route('trainers.index')->with('status', 'تم حذف المدرب');
    }

    protected function trainersView(Request $request, ?Trainer $editedTrainer = null)
    {
        return $this->dashboardView($request, 'trainers.index', [
            'pageTitle' => 'المدربين',
            'trainers' => $this->scopedTrainersQuery($request)->get(),
            'editedTrainer' => $editedTrainer,
            'transferTypes' => Trainer::transferTypeOptions(),
        ], 'trainers');
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

    protected function validatedPayload(Request $request, Branch $branch, ?Trainer $trainer = null): array
    {
        $passwordRules = $trainer ? ['nullable', 'string', 'min:6'] : ['required', 'string', 'min:6'];

        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30', Rule::unique('trainers', 'phone')->ignore($trainer?->id)],
            'password' => $passwordRules,
            'hourly_rate' => ['required', 'numeric', 'min:0'],
            'transfer_number' => ['required', 'string', 'max:255'],
            'transfer_type' => ['required', Rule::in(array_keys(Trainer::transferTypeOptions()))],
        ], [
            'name.required' => 'اسم المدرب مطلوب',
            'phone.required' => 'رقم الهاتف مطلوب',
            'phone.unique' => 'رقم الهاتف مسجل بالفعل',
            'password.required' => 'كلمة المرور مطلوبة',
            'password.min' => 'كلمة المرور يجب أن تكون 6 أحرف على الأقل',
            'hourly_rate.required' => 'سعر الساعة مطلوب',
            'hourly_rate.numeric' => 'سعر الساعة غير صحيح',
            'transfer_number.required' => 'رقم التحويل مطلوب',
            'transfer_type.required' => 'نوع التحويل مطلوب',
        ]) + ['branch_id' => $branch->id];
    }
}
