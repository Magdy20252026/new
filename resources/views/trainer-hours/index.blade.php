@extends('layouts.dashboard')

@php
    $formatNumber = static fn ($value) => rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.');
@endphp

@section('content')
    <div class="stacked-content">
        <section class="stats-grid trainer-hours-stats">
            <div class="stat-card">
                <div class="stat-card-icon"><i class="bi bi-calendar-date"></i></div>
                <div class="stat-card-label">التاريخ</div>
                <div class="stat-card-value trainer-hours-date-value">{{ $activeDate }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-card-icon"><i class="bi bi-calendar2-check-fill"></i></div>
                <div class="stat-card-label">الحضور</div>
                <div class="stat-card-value">{{ $attendanceCount }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-card-icon"><i class="bi bi-calendar2-x-fill"></i></div>
                <div class="stat-card-label">الغياب</div>
                <div class="stat-card-value">{{ $absenceCount }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-card-icon"><i class="bi bi-clock-history"></i></div>
                <div class="stat-card-label">إجمالي الساعات</div>
                <div class="stat-card-value">{{ $formatNumber($totalHours) }}</div>
            </div>
            @if($isManager)
                <div class="stat-card">
                    <div class="stat-card-icon"><i class="bi bi-cash-stack"></i></div>
                    <div class="stat-card-label">إجمالي الرواتب</div>
                    <div class="stat-card-value">{{ $formatNumber($totalPay) }}</div>
                </div>
            @endif
        </section>

        <section class="panel-card trainer-form-card trainer-hours-card">
            <h2 class="section-title">عرض اليوم</h2>
            <form action="{{ route('trainer-hours.index') }}" method="GET" class="form-grid trainer-hours-filter-form">
                <div>
                    <label class="form-label" for="active_date">التاريخ</label>
                    <input type="date" id="active_date" name="date" class="form-control" value="{{ $activeDate }}">
                </div>
                <div class="form-submit-row trainer-hours-submit-row">
                    <button type="submit" class="btn primary-btn">عرض</button>
                </div>
            </form>
        </section>

        <section class="panel-card trainer-form-card trainer-hours-card">
            <h2 class="section-title">{{ $editedTrainerHour ? 'تعديل ساعات المدرب' : 'إضافة ساعات المدرب' }}</h2>
            <form
                action="{{ $editedTrainerHour ? route('trainer-hours.update', $editedTrainerHour) : route('trainer-hours.store') }}"
                method="POST"
                class="form-grid trainer-hours-form"
            >
                @csrf
                @if($editedTrainerHour)
                    @method('PUT')
                @endif

                <div>
                    <label class="form-label" for="trainer_hour_date">التاريخ</label>
                    <input
                        type="date"
                        id="trainer_hour_date"
                        name="worked_on"
                        class="form-control"
                        value="{{ old('worked_on', $editedTrainerHour?->worked_on?->toDateString() ?? $activeDate) }}"
                    >
                </div>

                <div>
                    <label class="form-label" for="trainer_hour_trainer">المدرب</label>
                    <select
                        id="trainer_hour_trainer"
                        name="trainer_id"
                        class="form-select"
                        data-trainer-hours-trainer
                    >
                        <option value=""></option>
                        @foreach($trainers as $trainer)
                            <option
                                value="{{ $trainer->id }}"
                                data-hourly-rate="{{ $trainer->hourly_rate }}"
                                @selected((string) old('trainer_id', $editedTrainerHour?->trainer_id) === (string) $trainer->id)
                            >
                                {{ $trainer->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="form-label" for="trainer_hour_hours">عدد الساعات</label>
                    <input
                        type="number"
                        id="trainer_hour_hours"
                        name="hours"
                        class="form-control"
                        min="0.25"
                        step="0.25"
                        value="{{ old('hours', $editedTrainerHour?->hours) }}"
                        data-trainer-hours-input
                    >
                </div>

                @if($isManager)
                    <div>
                        <label class="form-label" for="trainer_hour_total">إجمالي الراتب</label>
                        <input type="text" id="trainer_hour_total" class="form-control" readonly data-trainer-hours-total>
                    </div>
                @endif

                <div class="form-actions-row trainer-form-actions">
                    <button type="submit" class="btn primary-btn">{{ $editedTrainerHour ? 'حفظ' : 'إضافة' }}</button>
                    @if($editedTrainerHour)
                        <a href="{{ route('trainer-hours.index', ['date' => $activeDate]) }}" class="btn action-btn">إلغاء</a>
                    @endif
                </div>
            </form>
        </section>

        <section class="panel-card table-card wide-card trainer-table-card trainer-hours-table-card">
            <h2 class="section-title">جدول الحضور</h2>
            @if($trainerHours->isEmpty())
                <div class="empty-state">لا توجد سجلات</div>
            @else
                <div class="table-responsive">
                    <table class="table align-middle app-table">
                        <thead>
                            <tr>
                                <th>اسم المدرب</th>
                                <th>عدد الساعات</th>
                                @if($isManager)
                                    <th>إجمالي الراتب</th>
                                @endif
                                <th class="table-actions">الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($trainerHours as $trainerHour)
                                @php($dailyPay = (float) $trainerHour->hours * (float) $trainerHour->trainer->hourly_rate)
                                <tr>
                                    <td>
                                        <div class="trainer-name-cell">
                                            <span class="trainer-avatar"><i class="bi bi-person-workspace"></i></span>
                                            <span>{{ $trainerHour->trainer->name }}</span>
                                        </div>
                                    </td>
                                    <td>{{ $formatNumber($trainerHour->hours) }}</td>
                                    @if($isManager)
                                        <td>{{ $formatNumber($dailyPay) }}</td>
                                    @endif
                                    <td class="table-actions">
                                        <div class="table-action-group">
                                            <a href="{{ route('trainer-hours.edit', [$trainerHour, 'date' => $activeDate]) }}" class="btn action-btn">تعديل</a>
                                            <form action="{{ route('trainer-hours.destroy', $trainerHour) }}" method="POST" class="inline-form">
                                                @csrf
                                                @method('DELETE')
                                                <input type="hidden" name="date" value="{{ $activeDate }}">
                                                <button type="submit" class="btn action-btn danger-btn">حذف</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        <section class="panel-card table-card wide-card trainer-table-card trainer-hours-table-card">
            <h2 class="section-title">جدول الغياب</h2>
            @if($absentTrainers->isEmpty())
                <div class="empty-state">لا توجد حالات غياب</div>
            @else
                <div class="table-responsive">
                    <table class="table align-middle app-table">
                        <thead>
                            <tr>
                                <th>اسم المدرب</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($absentTrainers as $trainer)
                                <tr>
                                    <td>
                                        <div class="trainer-name-cell">
                                            <span class="trainer-avatar"><i class="bi bi-person-workspace"></i></span>
                                            <span>{{ $trainer->name }}</span>
                                        </div>
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
