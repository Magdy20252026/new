@extends('layouts.dashboard')

@php
    $formatNumber = static fn ($value) => rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.');
    $initialTrainingDays = (int) old('training_days_per_week', $editedTrainingGroup?->training_days_per_week ?? 1);
    $initialSchedule = old('schedule', $editedTrainingGroup?->schedule ?? []);
    $initialName = old('generated_name', $editedTrainingGroup?->name ?? '');
@endphp

@section('content')
    <div class="stacked-content">
        <section class="stats-grid training-groups-stats">
            <div class="stat-card">
                <div class="stat-card-icon"><i class="bi bi-collection-fill"></i></div>
                <div class="stat-card-label">إجمالي المجموعات</div>
                <div class="stat-card-value">{{ $trainingGroups->count() }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-card-icon"><i class="bi bi-person-arms-up"></i></div>
                <div class="stat-card-label">السعة الإجمالية</div>
                <div class="stat-card-value">{{ $trainingGroups->sum('max_swimmers') }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-card-icon"><i class="bi bi-person-workspace"></i></div>
                <div class="stat-card-label">المدربون المتاحون</div>
                <div class="stat-card-value">{{ $trainers->count() }}</div>
            </div>
        </section>

        <section class="panel-card trainer-form-card training-groups-form-card">
            <h2 class="section-title">{{ $editedTrainingGroup ? 'تعديل مجموعة' : 'إضافة مجموعة' }}</h2>
            <form
                action="{{ $editedTrainingGroup ? route('training-groups.update', $editedTrainingGroup) : route('training-groups.store') }}"
                method="POST"
                class="form-grid top-form-grid training-groups-form"
                data-training-group-form
                data-week-days='@json($weekDays)'
                data-initial-schedule='@json($initialSchedule)'
            >
                @csrf
                @if($editedTrainingGroup)
                    @method('PUT')
                @endif

                <div class="training-groups-name-block">
                    <label class="form-label" for="training_group_name_preview">اسم المجموعة</label>
                    <input
                        type="text"
                        id="training_group_name_preview"
                        class="form-control"
                        value="{{ $initialName }}"
                        readonly
                        data-training-group-name
                    >
                </div>

                <div>
                    <label class="form-label" for="training_group_level">مستوى المجموعة</label>
                    <select id="training_group_level" name="level" class="form-select" data-training-group-level>
                        <option value=""></option>
                        @foreach($groupLevels as $level)
                            <option value="{{ $level }}" @selected(old('level', $editedTrainingGroup?->level) === $level)>{{ $level }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="form-label" for="training_group_trainer">المدرب</label>
                    <select id="training_group_trainer" name="trainer_id" class="form-select" data-training-group-trainer>
                        <option value=""></option>
                        @foreach($trainers as $trainer)
                            <option
                                value="{{ $trainer->id }}"
                                data-trainer-name="{{ $trainer->name }}"
                                @selected((string) old('trainer_id', $editedTrainingGroup?->trainer_id) === (string) $trainer->id)
                            >
                                {{ $trainer->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="form-label" for="training_group_days">عدد أيام التمرين خلال الأسبوع</label>
                    <input
                        type="number"
                        id="training_group_days"
                        name="training_days_per_week"
                        class="form-control"
                        min="1"
                        max="7"
                        value="{{ $initialTrainingDays }}"
                        data-training-group-days
                    >
                </div>

                <div>
                    <label class="form-label" for="training_group_available_days">عدد أيام التمارين المتاحة للمجموعة</label>
                    <input
                        type="number"
                        id="training_group_available_days"
                        name="available_training_days"
                        class="form-control"
                        min="1"
                        max="7"
                        value="{{ old('available_training_days', $editedTrainingGroup?->available_training_days) }}"
                    >
                </div>

                <div>
                    <label class="form-label" for="training_group_max_swimmers">أقصى عدد للسباحين في المجموعة</label>
                    <input
                        type="number"
                        id="training_group_max_swimmers"
                        name="max_swimmers"
                        class="form-control"
                        min="1"
                        value="{{ old('max_swimmers', $editedTrainingGroup?->max_swimmers) }}"
                    >
                </div>

                <div>
                    <label class="form-label" for="training_group_price">سعر المجموعة</label>
                    <input
                        type="number"
                        id="training_group_price"
                        name="price"
                        class="form-control"
                        min="0"
                        step="0.01"
                        value="{{ old('price', $editedTrainingGroup?->price) }}"
                    >
                </div>

                <div class="training-groups-schedule-block">
                    <label class="form-label">مواعيد التمرين</label>
                    <div class="training-groups-schedule-list" data-training-group-schedule></div>
                </div>

                <div class="form-actions-row trainer-form-actions training-groups-actions">
                    <button type="submit" class="btn primary-btn">{{ $editedTrainingGroup ? 'حفظ' : 'إضافة' }}</button>
                    @if($editedTrainingGroup)
                        <a href="{{ route('training-groups.index') }}" class="btn action-btn">إلغاء</a>
                    @endif
                </div>
            </form>
        </section>

        <section class="panel-card table-card wide-card trainer-table-card training-groups-table-card">
            <h2 class="section-title">جدول المجموعات المسجلة</h2>
            @if($trainingGroups->isEmpty())
                <div class="empty-state">لا توجد مجموعات</div>
            @else
                <div class="table-responsive">
                    <table class="table align-middle app-table">
                        <thead>
                            <tr>
                                <th>اسم المجموعة</th>
                                <th>المستوى</th>
                                <th>المدرب</th>
                                <th>مواعيد التمرين</th>
                                <th>أيام التمرين</th>
                                <th>الأيام المتاحة</th>
                                <th>أقصى عدد</th>
                                <th>السعر</th>
                                <th class="table-actions">الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($trainingGroups as $trainingGroup)
                                <tr>
                                    <td>
                                        <div class="training-groups-name-cell">
                                            <span class="trainer-avatar"><i class="bi bi-collection-fill"></i></span>
                                            <span>{{ $trainingGroup->name }}</span>
                                        </div>
                                    </td>
                                    <td><span class="pill-badge">{{ $trainingGroup->level }}</span></td>
                                    <td>{{ $trainingGroup->trainer->name }}</td>
                                    <td>
                                        <div class="training-groups-schedule-chips">
                                            @foreach($trainingGroup->schedule ?? [] as $entry)
                                                <span class="pill-badge">
                                                    {{ $weekDays[$entry['day']] ?? $entry['day'] }}
                                                    {{ mb_substr($entry['time'] ?? '', 0, 5) }}
                                                </span>
                                            @endforeach
                                        </div>
                                    </td>
                                    <td>{{ $trainingGroup->training_days_per_week }}</td>
                                    <td>{{ $trainingGroup->available_training_days }}</td>
                                    <td>{{ $trainingGroup->max_swimmers }}</td>
                                    <td>{{ $formatNumber($trainingGroup->price) }}</td>
                                    <td class="table-actions">
                                        <div class="table-action-group">
                                            <a href="{{ route('training-groups.edit', $trainingGroup) }}" class="btn action-btn">تعديل</a>
                                            <form action="{{ route('training-groups.destroy', $trainingGroup) }}" method="POST" class="inline-form">
                                                @csrf
                                                @method('DELETE')
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
