@extends('layouts.dashboard')

@php
    $formatNumber = static fn ($value) => rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.');
@endphp

@section('content')
    <div class="stacked-content">
        <section class="stats-grid trainer-advances-stats">
            <div class="stat-card">
                <div class="stat-card-icon"><i class="bi bi-calendar-date"></i></div>
                <div class="stat-card-label">التاريخ</div>
                <div class="stat-card-value trainer-advances-date-value">{{ $activeDate }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-card-icon"><i class="bi bi-wallet2"></i></div>
                <div class="stat-card-label">عدد السلف</div>
                <div class="stat-card-value">{{ $advanceCount }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-card-icon"><i class="bi bi-people-fill"></i></div>
                <div class="stat-card-label">المدربين</div>
                <div class="stat-card-value">{{ $trainerCount }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-card-icon"><i class="bi bi-cash-stack"></i></div>
                <div class="stat-card-label">إجمالي السلف</div>
                <div class="stat-card-value">{{ $formatNumber($totalAmount) }}</div>
            </div>
        </section>

        <section class="panel-card trainer-form-card trainer-advances-panel">
            <form action="{{ route('trainer-advances.index') }}" method="GET" class="form-grid trainer-advances-toolbar">
                <div>
                    <label class="form-label" for="trainer_advance_active_date">تاريخ العرض</label>
                    <input
                        type="date"
                        id="trainer_advance_active_date"
                        name="date"
                        class="form-control"
                        value="{{ $activeDate }}"
                    >
                </div>

                <div class="form-submit-row trainer-advances-filter-actions">
                    <button type="submit" class="btn primary-btn">عرض</button>
                </div>
            </form>

            <form
                action="{{ $editedTrainerAdvance ? route('trainer-advances.update', $editedTrainerAdvance) : route('trainer-advances.store') }}"
                method="POST"
                class="form-grid trainer-advances-form"
            >
                @csrf
                @if($editedTrainerAdvance)
                    @method('PUT')
                @endif

                <div>
                    <label class="form-label" for="trainer_advance_date">تاريخ السلفة</label>
                    <input
                        type="date"
                        id="trainer_advance_date"
                        name="advanced_on"
                        class="form-control"
                        value="{{ old('advanced_on', $editedTrainerAdvance?->advanced_on?->toDateString() ?? $activeDate) }}"
                    >
                </div>

                <div>
                    <label class="form-label" for="trainer_advance_trainer">المدرب</label>
                    <select id="trainer_advance_trainer" name="trainer_id" class="form-select">
                        <option value=""></option>
                        @foreach($trainers as $trainer)
                            <option
                                value="{{ $trainer->id }}"
                                @selected((string) old('trainer_id', $editedTrainerAdvance?->trainer_id) === (string) $trainer->id)
                            >
                                {{ $trainer->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="form-label" for="trainer_advance_amount">المبلغ</label>
                    <input
                        type="number"
                        id="trainer_advance_amount"
                        name="amount"
                        class="form-control"
                        min="0.01"
                        step="0.01"
                        value="{{ old('amount', $editedTrainerAdvance?->amount) }}"
                    >
                </div>

                <div class="form-actions-row trainer-form-actions trainer-advances-actions">
                    <button type="submit" class="btn primary-btn">{{ $editedTrainerAdvance ? 'حفظ' : 'إضافة' }}</button>
                    @if($editedTrainerAdvance)
                        <a href="{{ route('trainer-advances.index', ['date' => $activeDate]) }}" class="btn action-btn">إلغاء</a>
                    @endif
                </div>
            </form>
        </section>

        <section class="panel-card table-card wide-card trainer-table-card trainer-advances-table-card">
            <h2 class="section-title">جدول سلف المدربين</h2>
            @if($trainerAdvances->isEmpty())
                <div class="empty-state">لا توجد سلف في هذا اليوم</div>
            @else
                <div class="table-responsive">
                    <table class="table align-middle app-table">
                        <thead>
                            <tr>
                                <th>اسم المدرب</th>
                                <th>المبلغ</th>
                                <th>وقت الإضافة</th>
                                <th class="table-actions">الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($trainerAdvances as $trainerAdvance)
                                <tr>
                                    <td>
                                        <div class="trainer-name-cell">
                                            <span class="trainer-avatar"><i class="bi bi-person-workspace"></i></span>
                                            <span>{{ $trainerAdvance->trainer->name }}</span>
                                        </div>
                                    </td>
                                    <td class="trainer-rate-cell">{{ $formatNumber($trainerAdvance->amount) }}</td>
                                    <td>{{ $trainerAdvance->created_at?->format('H:i') }}</td>
                                    <td class="table-actions">
                                        <div class="table-action-group">
                                            <a href="{{ route('trainer-advances.edit', [$trainerAdvance, 'date' => $activeDate]) }}" class="btn action-btn">تعديل</a>
                                            <form action="{{ route('trainer-advances.destroy', $trainerAdvance) }}" method="POST" class="inline-form">
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
    </div>
@endsection
