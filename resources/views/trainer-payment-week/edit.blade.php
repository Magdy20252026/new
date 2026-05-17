@extends('layouts.dashboard')

@section('content')
    <section class="panel-card form-panel payout-week-panel">
        <form action="{{ route('trainer-payment-week.update') }}" method="POST" class="form-grid payout-week-form">
            @csrf
            @method('PUT')

            <div class="payout-week-fields">
                <div class="payout-week-field-card">
                    <label class="form-label" for="trainer_payment_week_start">يوم بداية القبض</label>
                    <select id="trainer_payment_week_start" name="trainer_payment_week_start" class="form-select">
                        @foreach($weekDays as $value => $label)
                            <option
                                value="{{ $value }}"
                                @selected(old('trainer_payment_week_start', $paymentWeek['start_day']) === $value)
                            >
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="payout-week-field-card">
                    <label class="form-label" for="trainer_payment_week_end">يوم نهاية القبض</label>
                    <select id="trainer_payment_week_end" name="trainer_payment_week_end" class="form-select">
                        @foreach($weekDays as $value => $label)
                            <option
                                value="{{ $value }}"
                                @selected(old('trainer_payment_week_end', $paymentWeek['end_day']) === $value)
                            >
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="form-actions-row">
                <button type="submit" class="btn primary-btn">حفظ إعدادات أسبوع القبض</button>
            </div>
        </form>
    </section>
@endsection
