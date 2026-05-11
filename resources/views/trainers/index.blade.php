@extends('layouts.dashboard')

@section('content')
    <div class="stacked-content">
        <section class="panel-card trainer-form-card">
            <h2 class="section-title">{{ $editedTrainer ? 'تعديل مدرب' : 'إضافة مدرب' }}</h2>
            <form
                action="{{ $editedTrainer ? route('trainers.update', $editedTrainer) : route('trainers.store') }}"
                method="POST"
                class="form-grid top-form-grid trainer-form"
            >
                @csrf
                @if($editedTrainer)
                    @method('PUT')
                @endif

                <div>
                    <label class="form-label" for="trainer_name">اسم المدرب</label>
                    <input
                        type="text"
                        id="trainer_name"
                        name="name"
                        class="form-control"
                        value="{{ old('name', $editedTrainer?->name) }}"
                    >
                </div>

                <div>
                    <label class="form-label" for="trainer_phone">رقم الهاتف</label>
                    <input
                        type="text"
                        id="trainer_phone"
                        name="phone"
                        class="form-control"
                        value="{{ old('phone', $editedTrainer?->phone) }}"
                    >
                </div>

                <div>
                    <label class="form-label" for="trainer_password">كلمة المرور</label>
                    <input type="password" id="trainer_password" name="password" class="form-control">
                </div>

                <div>
                    <label class="form-label" for="hourly_rate">سعر الساعة</label>
                    <input
                        type="number"
                        id="hourly_rate"
                        name="hourly_rate"
                        class="form-control"
                        min="0"
                        step="0.01"
                        value="{{ old('hourly_rate', $editedTrainer?->hourly_rate) }}"
                    >
                </div>

                <div>
                    <label class="form-label" for="transfer_number">رقم التحويل</label>
                    <input
                        type="text"
                        id="transfer_number"
                        name="transfer_number"
                        class="form-control"
                        value="{{ old('transfer_number', $editedTrainer?->transfer_number) }}"
                    >
                </div>

                <div>
                    <label class="form-label" for="transfer_type">نوع التحويل</label>
                    <select id="transfer_type" name="transfer_type" class="form-select trainer-transfer-select">
                        @foreach($transferTypes as $value => $label)
                            <option value="{{ $value }}" @selected(old('transfer_type', $editedTrainer?->transfer_type) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-actions-row trainer-form-actions">
                    <button type="submit" class="btn primary-btn">{{ $editedTrainer ? 'حفظ' : 'إضافة' }}</button>
                    @if($editedTrainer)
                        <a href="{{ route('trainers.index') }}" class="btn action-btn">إلغاء</a>
                    @endif
                </div>
            </form>
        </section>

        <section class="panel-card table-card wide-card trainer-table-card">
            <h2 class="section-title">جدول المدربين</h2>
            <div class="table-responsive">
                <table class="table align-middle app-table">
                    <thead>
                        <tr>
                            <th>اسم المدرب</th>
                            <th>رقم الهاتف</th>
                            <th>سعر الساعة</th>
                            <th>رقم التحويل</th>
                            <th>نوع التحويل</th>
                            <th class="table-actions">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($trainers as $trainer)
                            <tr>
                                <td>
                                    <div class="trainer-name-cell">
                                        <span class="trainer-avatar"><i class="bi bi-person-workspace"></i></span>
                                        <span>{{ $trainer->name }}</span>
                                    </div>
                                </td>
                                <td>{{ $trainer->phone }}</td>
                                <td class="trainer-rate-cell">{{ rtrim(rtrim(number_format((float) $trainer->hourly_rate, 2, '.', ''), '0'), '.') }}</td>
                                <td>{{ $trainer->transfer_number }}</td>
                                <td><span class="pill-badge">{{ $trainer->transferTypeLabel() }}</span></td>
                                <td class="table-actions">
                                    <div class="table-action-group">
                                        <a href="{{ route('trainers.edit', $trainer) }}" class="btn action-btn">تعديل</a>
                                        <form action="{{ route('trainers.destroy', $trainer) }}" method="POST" class="inline-form">
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
        </section>
    </div>
@endsection
