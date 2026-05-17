@php
    $formatNumber = static fn ($value) => rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.');
@endphp

@extends('layouts.dashboard')

@section('content')
    <div class="stacked-content">
        <section class="panel-card trainer-form-card">
            @if($setupError)
                <div class="alert alert-danger panel-alert mb-3">{{ $setupError }}</div>
            @endif

            <h2 class="section-title">{{ $editedAdministrator ? 'تعديل إداري' : 'إضافة إداري' }}</h2>
            <form
                action="{{ $editedAdministrator ? route('administrators.update', $editedAdministrator) : route('administrators.store') }}"
                method="POST"
                class="form-grid top-form-grid administrator-form"
            >
                @csrf
                @if($editedAdministrator)
                    @method('PUT')
                @endif

                <div>
                    <label class="form-label" for="administrator_name">اسم الإداري</label>
                    <input
                        type="text"
                        id="administrator_name"
                        name="name"
                        class="form-control"
                        value="{{ old('name', $editedAdministrator?->name) }}"
                        @disabled($setupError)
                    >
                </div>

                <div>
                    <label class="form-label" for="administrator_phone">رقم الهاتف</label>
                    <input
                        type="text"
                        id="administrator_phone"
                        name="phone"
                        class="form-control"
                        value="{{ old('phone', $editedAdministrator?->phone) }}"
                        @disabled($setupError)
                    >
                </div>

                <div>
                    <label class="form-label" for="administrator_job_title">الوظيفة</label>
                    <input
                        type="text"
                        id="administrator_job_title"
                        name="job_title"
                        class="form-control"
                        value="{{ old('job_title', $editedAdministrator?->job_title) }}"
                        @disabled($setupError)
                    >
                </div>

                <div>
                    <label class="form-label" for="administrator_salary">الراتب</label>
                    <input
                        type="number"
                        id="administrator_salary"
                        name="salary"
                        class="form-control"
                        min="0"
                        step="0.01"
                        value="{{ old('salary', $editedAdministrator?->salary) }}"
                        @disabled($setupError)
                    >
                </div>

                <div class="form-actions-row trainer-form-actions">
                    <button type="submit" class="btn primary-btn" @disabled($setupError)>{{ $editedAdministrator ? 'حفظ' : 'إضافة' }}</button>
                    @if($editedAdministrator)
                        <a href="{{ route('administrators.index') }}" class="btn action-btn">إلغاء</a>
                    @endif
                </div>
            </form>
        </section>

        <section class="panel-card table-card wide-card trainer-table-card">
            <h2 class="section-title">جدول الإداريين</h2>
            <div class="table-responsive">
                <table class="table align-middle app-table">
                    <thead>
                        <tr>
                            <th>اسم الإداري</th>
                            <th>رقم الهاتف</th>
                            <th>الوظيفة</th>
                            <th>الراتب</th>
                            <th class="table-actions">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($administrators as $administrator)
                            <tr>
                                <td>
                                    <div class="administrator-name-cell">
                                        <span class="administrator-avatar"><i class="bi bi-person-badge"></i></span>
                                        <span>{{ $administrator->name }}</span>
                                    </div>
                                </td>
                                <td>{{ $administrator->phone }}</td>
                                <td><span class="pill-badge">{{ $administrator->job_title }}</span></td>
                                <td class="administrator-salary-cell">{{ $formatNumber($administrator->salary) }}</td>
                                <td class="table-actions">
                                    <div class="table-action-group">
                                        <a href="{{ route('administrators.edit', $administrator) }}" class="btn action-btn">تعديل</a>
                                        <form action="{{ route('administrators.destroy', $administrator) }}" method="POST" class="inline-form">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn action-btn danger-btn">حذف</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">
                                    {{ $setupError ?: 'لا يوجد إداريون مضافون حالياً.' }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
@endsection
