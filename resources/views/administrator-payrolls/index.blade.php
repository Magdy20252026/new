@extends('layouts.dashboard')

@php
    $formatNumber = static fn ($value) => rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.');
    $formatDate = static fn ($value) => optional($value)->format('Y-m-d');
@endphp

@section('content')
    <div class="stacked-content">
        <section class="stats-grid administrator-payrolls-stats">
            <div class="stat-card">
                <div class="stat-card-icon"><i class="bi bi-calendar-month-fill"></i></div>
                <div class="stat-card-label">بداية الشهر</div>
                <div class="stat-card-value trainer-payrolls-date-value">{{ $currentMonth['start']->format('Y-m-d') }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-card-icon"><i class="bi bi-calendar-check-fill"></i></div>
                <div class="stat-card-label">نهاية الشهر</div>
                <div class="stat-card-value trainer-payrolls-date-value">{{ $currentMonth['end']->format('Y-m-d') }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-card-icon"><i class="bi bi-person-check-fill"></i></div>
                <div class="stat-card-label">المرتبات المصروفة</div>
                <div class="stat-card-value">{{ $paidPayrollCount }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-card-icon"><i class="bi bi-cash-coin"></i></div>
                <div class="stat-card-label">إجمالي المصروف</div>
                <div class="stat-card-value">{{ $formatNumber($paidTotalAmount) }}</div>
            </div>
        </section>

        <section class="panel-card trainer-payrolls-panel administrator-payrolls-panel">
            @if($setupError)
                <div class="alert alert-danger panel-alert mb-3">{{ $setupError }}</div>
            @endif

            <form action="{{ route('administrator-payrolls.index') }}" method="GET" class="form-grid trainer-payrolls-filter-form">
                <div>
                    <label class="form-label" for="administrator_payroll_administrator">الإداري</label>
                    <select
                        id="administrator_payroll_administrator"
                        name="administrator_id"
                        class="form-select trainer-payroll-select administrator-payroll-select"
                        data-auto-submit-select
                    >
                        <option value=""></option>
                        @foreach($availableAdministrators as $administrator)
                            <option value="{{ $administrator->id }}" @selected((string) request('administrator_id') === (string) $administrator->id)>
                                {{ $administrator->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </form>

            <div class="administrator-payroll-profile-card">
                <div class="administrator-payroll-profile-head">
                    <span class="administrator-avatar administrator-payroll-avatar"><i class="bi bi-person-badge-fill"></i></span>
                    <div>
                        <div class="administrator-payroll-profile-name">{{ $selectedAdministrator?->name }}</div>
                        <div class="administrator-payroll-profile-role">{{ $selectedAdministrator?->job_title }}</div>
                    </div>
                </div>

                <div class="trainer-payrolls-summary-grid administrator-payrolls-summary-grid">
                    <div class="trainer-payroll-summary-card">
                        <div class="trainer-payroll-summary-label">رقم الهاتف</div>
                        <div class="trainer-payroll-summary-value">{{ $selectedAdministrator?->phone }}</div>
                    </div>
                    <div class="trainer-payroll-summary-card">
                        <div class="trainer-payroll-summary-label">الوظيفة</div>
                        <div class="trainer-payroll-summary-value">{{ $selectedAdministrator?->job_title }}</div>
                    </div>
                    <div class="trainer-payroll-summary-card administrator-payroll-salary-card">
                        <div class="trainer-payroll-summary-label">الراتب</div>
                        <div class="trainer-payroll-summary-value">{{ $formatNumber($selectedAdministrator?->salary) }}</div>
                    </div>
                </div>

                <div class="trainer-payrolls-actions">
                    <form action="{{ route('administrator-payrolls.store') }}" method="POST" class="inline-form">
                        @csrf
                        <input type="hidden" name="administrator_id" value="{{ $selectedAdministrator?->id }}">
                        <button type="submit" class="btn primary-btn" @disabled(! $selectedAdministrator)>
                            صرف الراتب
                        </button>
                    </form>
                </div>
            </div>
        </section>

        <section class="panel-card table-card wide-card trainer-payrolls-table-card">
            <h2 class="section-title">جدول رواتب الإداريين</h2>
            @if($paidPayrolls->isEmpty())
                <div class="empty-state">لا توجد سجلات</div>
            @else
                <div class="table-responsive">
                    <table class="table align-middle app-table">
                        <thead>
                            <tr>
                                <th>اسم الإداري</th>
                                <th>رقم الهاتف</th>
                                <th>الوظيفة</th>
                                <th>الشهر</th>
                                <th>الراتب</th>
                                <th>تاريخ الصرف</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($paidPayrolls as $administratorPayroll)
                                <tr>
                                    <td>
                                        <div class="administrator-name-cell">
                                            <span class="administrator-avatar"><i class="bi bi-person-badge"></i></span>
                                            <span>{{ $administratorPayroll->administrator->name }}</span>
                                        </div>
                                    </td>
                                    <td>{{ $administratorPayroll->administrator->phone }}</td>
                                    <td><span class="pill-badge">{{ $administratorPayroll->administrator->job_title }}</span></td>
                                    <td>{{ $administratorPayroll->period_start->format('Y-m') }}</td>
                                    <td>{{ $formatNumber($administratorPayroll->amount) }}</td>
                                    <td>{{ $formatDate($administratorPayroll->paid_at) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>
@endsection
