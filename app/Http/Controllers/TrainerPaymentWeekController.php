<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Support\ControlPanel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TrainerPaymentWeekController extends Controller
{
    public function edit(Request $request)
    {
        return $this->dashboardView($request, 'trainer-payment-week.edit', [
            'pageTitle' => 'بداية أسبوع قبض المدربين',
            'paymentWeek' => ControlPanel::trainerPaymentWeek(),
            'weekDays' => ControlPanel::trainerPaymentWeekDayOptions(),
        ], 'trainer-payment-week');
    }

    public function update(Request $request): RedirectResponse
    {
        $weekDays = array_keys(ControlPanel::trainerPaymentWeekDayOptions());

        $data = $request->validate([
            'trainer_payment_week_start' => ['required', Rule::in($weekDays)],
            'trainer_payment_week_end' => ['required', Rule::in($weekDays)],
        ], [
            'trainer_payment_week_start.required' => 'يرجى اختيار يوم بداية القبض.',
            'trainer_payment_week_end.required' => 'يرجى اختيار يوم نهاية القبض.',
        ]);

        AppSetting::putValue('trainer_payment_week_start', $data['trainer_payment_week_start']);
        AppSetting::putValue('trainer_payment_week_end', $data['trainer_payment_week_end']);

        return redirect()
            ->route('trainer-payment-week.edit')
            ->with('status', 'تم حفظ بداية ونهاية أسبوع قبض المدربين بنجاح');
    }
}
