@extends('layouts.dashboard')

@php
    $formatNumber = static fn ($value) => rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.');
    $formatDate = static fn ($value) => optional($value)->format('Y-m-d');
@endphp

@section('content')
    <div class="stacked-content">
        <section class="stats-grid trainer-payrolls-stats">
            <div class="stat-card">
                <div class="stat-card-icon"><i class="bi bi-calendar-range"></i></div>
                <div class="stat-card-label">بداية الفترة</div>
                <div class="stat-card-value trainer-payrolls-date-value">{{ $currentPeriod['start']->format('Y-m-d') }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-card-icon"><i class="bi bi-calendar-check-fill"></i></div>
                <div class="stat-card-label">نهاية الفترة</div>
                <div class="stat-card-value trainer-payrolls-date-value">{{ $currentPeriod['end']->format('Y-m-d') }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-card-icon"><i class="bi bi-cash-stack"></i></div>
                <div class="stat-card-label">المرتبات المصروفة</div>
                <div class="stat-card-value">{{ $paidPayrollCount }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-card-icon"><i class="bi bi-safe2-fill"></i></div>
                <div class="stat-card-label">المرتبات المحجوزة</div>
                <div class="stat-card-value">{{ $heldPayrollCount }}</div>
            </div>
        </section>

        <section class="panel-card trainer-payrolls-panel">
            @if($setupError)
                <div class="alert alert-danger panel-alert mb-3">{{ $setupError }}</div>
            @endif

            <form action="{{ route('trainer-payrolls.index') }}" method="GET" class="form-grid trainer-payrolls-filter-form">
                <div>
                    <label class="form-label" for="trainer_payroll_trainer">المدرب</label>
                    <select
                        id="trainer_payroll_trainer"
                        name="trainer_id"
                        class="form-select trainer-payroll-select"
                        data-auto-submit-select
                    >
                        <option value=""></option>
                        @foreach($trainers as $trainer)
                            <option value="{{ $trainer->id }}" @selected((string) request('trainer_id') === (string) $trainer->id)>
                                {{ $trainer->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </form>

            <div class="trainer-payrolls-summary-grid">
                <div class="trainer-payroll-summary-card">
                    <div class="trainer-payroll-summary-label">عدد الساعات</div>
                    <div class="trainer-payroll-summary-value">{{ $formatNumber($selectedSummary['hours']) }}</div>
                </div>
                <div class="trainer-payroll-summary-card">
                    <div class="trainer-payroll-summary-label">سعر الساعة</div>
                    <div class="trainer-payroll-summary-value">{{ $formatNumber($selectedSummary['hourly_rate']) }}</div>
                </div>
                <div class="trainer-payroll-summary-card">
                    <div class="trainer-payroll-summary-label">إجمالي الراتب</div>
                    <div class="trainer-payroll-summary-value">{{ $formatNumber($selectedSummary['total_amount']) }}</div>
                </div>
                <div class="trainer-payroll-summary-card">
                    <div class="trainer-payroll-summary-label">إجمالي السلف</div>
                    <div class="trainer-payroll-summary-value">{{ $formatNumber($selectedSummary['advance_amount']) }}</div>
                </div>
                <div class="trainer-payroll-summary-card">
                    <div class="trainer-payroll-summary-label">الراتب النهائي</div>
                    <div class="trainer-payroll-summary-value">{{ $formatNumber($selectedSummary['net_amount']) }}</div>
                </div>
            </div>

            <div class="trainer-payrolls-actions">
                <form action="{{ route('trainer-payrolls.store') }}" method="POST" class="inline-form">
                    @csrf
                    <input type="hidden" name="trainer_id" value="{{ $selectedTrainer?->id }}">
                    <button
                        type="submit"
                        class="btn primary-btn"
                        @disabled(! $selectedTrainer || $selectedSummary['net_amount'] <= 0 || $selectedSummary['has_current_payroll'])
                    >
                        صرف الراتب
                    </button>
                </form>
            </div>
        </section>

        <section class="panel-card table-card wide-card trainer-payrolls-table-card">
            <h2 class="section-title">جدول المرتبات المصروفة</h2>
            @if($paidPayrolls->isEmpty())
                <div class="empty-state">لا توجد سجلات</div>
            @else
                <div class="table-responsive">
                    <table class="table align-middle app-table">
                        <thead>
                            <tr>
                                <th>اسم المدرب</th>
                                <th>عدد الساعات</th>
                                <th>سعر الساعة</th>
                                <th>إجمالي الراتب</th>
                                <th>إجمالي السلف</th>
                                <th>الراتب النهائي</th>
                                <th>تاريخ الصرف</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($paidPayrolls as $trainerPayroll)
                                <tr>
                                    <td>
                                        <div class="trainer-name-cell">
                                            <span class="trainer-avatar"><i class="bi bi-person-workspace"></i></span>
                                            <span>{{ $trainerPayroll->trainer->name }}</span>
                                        </div>
                                     </td>
                                     <td>{{ $formatNumber($trainerPayroll->hours) }}</td>
                                     <td>{{ $formatNumber($trainerPayroll->hourly_rate) }}</td>
                                     <td>{{ $formatNumber($trainerPayroll->total_amount) }}</td>
                                     <td>{{ $formatNumber($trainerPayroll->advance_amount) }}</td>
                                     <td>{{ $formatNumber($trainerPayroll->net_amount) }}</td>
                                     <td>{{ $formatDate($trainerPayroll->paid_at) }}</td>
                                 </tr>
                             @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        <section class="panel-card table-card wide-card trainer-payrolls-table-card">
            <h2 class="section-title">جدول الرواتب المحجوزة</h2>
            @if($heldPayrolls->isEmpty())
                <div class="empty-state">لا توجد سجلات</div>
            @else
                <div class="table-responsive">
                    <table class="table align-middle app-table">
                        <thead>
                            <tr>
                                <th>اسم المدرب</th>
                                <th>الفترة</th>
                                <th>عدد الساعات</th>
                                <th>سعر الساعة</th>
                                <th>إجمالي الراتب</th>
                                <th>إجمالي السلف</th>
                                <th>الراتب النهائي</th>
                                <th class="table-actions">الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($heldPayrolls as $trainerPayroll)
                                <tr>
                                    <td>
                                        <div class="trainer-name-cell">
                                            <span class="trainer-avatar"><i class="bi bi-person-workspace"></i></span>
                                            <span>{{ $trainerPayroll->trainer->name }}</span>
                                        </div>
                                    </td>
                                     <td>{{ $trainerPayroll->period_start->format('Y-m-d') }} - {{ $trainerPayroll->period_end->format('Y-m-d') }}</td>
                                     <td>{{ $formatNumber($trainerPayroll->hours) }}</td>
                                     <td>{{ $formatNumber($trainerPayroll->hourly_rate) }}</td>
                                     <td>{{ $formatNumber($trainerPayroll->total_amount) }}</td>
                                     <td>{{ $formatNumber($trainerPayroll->advance_amount) }}</td>
                                     <td>{{ $formatNumber($trainerPayroll->net_amount) }}</td>
                                     <td class="table-actions">
                                         <form action="{{ route('trainer-payrolls.release', $trainerPayroll) }}" method="POST" class="inline-form">
                                             @csrf
                                            <button type="submit" class="btn primary-btn">صرف الراتب</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>
@endsection
