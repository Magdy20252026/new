<?php

namespace App\Http\Controllers;

use App\Models\Trainer;
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
        $data = $this->validatedPayload($request);

        Trainer::query()->create($data);

        return redirect()->route('trainers.index')->with('status', 'تم إضافة المدرب');
    }

    public function edit(Request $request, Trainer $trainer)
    {
        return $this->trainersView($request, $trainer);
    }

    public function update(Request $request, Trainer $trainer): RedirectResponse
    {
        $data = $this->validatedPayload($request, $trainer);

        if (blank($data['password'])) {
            unset($data['password']);
        }

        $trainer->update($data);

        return redirect()->route('trainers.index')->with('status', 'تم تحديث المدرب');
    }

    public function destroy(Trainer $trainer): RedirectResponse
    {
        $trainer->delete();

        return redirect()->route('trainers.index')->with('status', 'تم حذف المدرب');
    }

    protected function trainersView(Request $request, ?Trainer $editedTrainer = null)
    {
        return $this->dashboardView($request, 'trainers.index', [
            'pageTitle' => 'المدربين',
            'trainers' => Trainer::query()->orderBy('name')->get(),
            'editedTrainer' => $editedTrainer,
            'transferTypes' => Trainer::transferTypeOptions(),
        ], 'trainers');
    }

    protected function validatedPayload(Request $request, ?Trainer $trainer = null): array
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
        ]);
    }
}
