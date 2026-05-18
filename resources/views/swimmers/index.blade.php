@extends('layouts.dashboard')

@php
    $formatNumber = static fn ($value) => rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.');
    $selectedGroupId = (string) old('training_group_id', $editedSwimmer?->training_group_id);
    $selectedGroup = $trainingGroups->firstWhere('id', (int) $selectedGroupId);
    $initialBirthYear = (int) old('birth_year', $editedSwimmer?->birth_year ?? $currentYear);
    $initialPaid = old('amount_paid', $editedSwimmer?->amount_paid ?? 0);
    $initialPrice = old('group_price', $editedSwimmer?->group_price ?? $selectedGroup?->price ?? 0);
    $excludeFromFinancialTotals = old('exclude_from_financial_totals', $editedSwimmer?->exclude_from_financial_totals ?? false);
    $countedSwimmers = $swimmers->reject(fn ($swimmer) => $swimmer->exclude_from_financial_totals);
    $showForm = $showCreateForm || $editedSwimmer !== null;
    $trainingGroupDataset = $trainingGroups->map(fn ($group) => [
        'id' => $group->id,
        'name' => $group->name,
        'price' => (float) $group->price,
        'training_days_per_week' => (int) $group->training_days_per_week,
        'available_training_days' => (int) $group->available_training_days,
    ])->values();
@endphp

@section('content')
    <div class="stacked-content">
        <section class="stats-grid training-groups-stats">
            <div class="stat-card">
                <div class="stat-card-icon"><i class="bi bi-person-arms-up"></i></div>
                <div class="stat-card-label">إجمالي السباحين</div>
                <div class="stat-card-value">{{ $swimmers->count() }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-card-icon"><i class="bi bi-credit-card-2-front-fill"></i></div>
                <div class="stat-card-label">إجمالي المدفوع</div>
                <div class="stat-card-value">{{ $formatNumber($countedSwimmers->sum('amount_paid')) }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-card-icon"><i class="bi bi-cash-stack"></i></div>
                <div class="stat-card-label">إجمالي المتبقي</div>
                <div class="stat-card-value">{{ $formatNumber($countedSwimmers->sum('remaining_amount')) }}</div>
            </div>
        </section>

        <section class="panel-card trainer-files-header-card">
            <div class="trainer-files-header">
                <div>
                    <h2 class="section-title mb-2">بيانات السباحين</h2>
                    <div class="form-help-text mt-0">يعرض الجدول كل السباحين المسجلين مع زر خاص بملفات كل لاعب.</div>
                </div>
                <div class="table-action-group">
                    @if($showForm)
                        <a href="{{ route('swimmers.index') }}" class="btn action-btn">إخفاء النموذج</a>
                    @endif
                    <a
                        href="{{ route('swimmers.index', ['create' => 1]) }}#swimmer-form-panel"
                        class="btn primary-btn"
                        data-swimmer-toggle-form
                    >
                        إضافة سباح
                    </a>
                </div>
            </div>
        </section>

        <section
            id="swimmer-form-panel"
            class="panel-card trainer-form-card swimmer-form-card {{ $showForm ? '' : 'd-none' }}"
            data-swimmer-form-panel
        >
            <h2 class="section-title">{{ $editedSwimmer ? 'تعديل سباح' : 'إضافة سباح' }}</h2>
            <form
                action="{{ $editedSwimmer ? route('swimmers.update', $editedSwimmer) : route('swimmers.store') }}"
                method="POST"
                class="form-grid top-form-grid swimmer-form"
                data-swimmer-form
                data-next-serial-number="{{ $nextSerialNumber }}"
                data-training-groups='@json($trainingGroupDataset)'
            >
                @csrf
                @if($editedSwimmer)
                    @method('PUT')
                @endif

                <div>
                    <label class="form-label" for="swimmer_barcode">باركود السباح</label>
                    <input
                        type="text"
                        id="swimmer_barcode"
                        class="form-control"
                        value="{{ old('barcode', $editedSwimmer?->barcode) }}"
                        readonly
                        data-swimmer-barcode
                    >
                    <div class="form-help-text">يبدأ الترقيم من 1001 ويزيد تلقائيًا.</div>
                </div>

                <div>
                    <label class="form-label" for="swimmer_name">اسم السباح</label>
                    <input
                        type="text"
                        id="swimmer_name"
                        name="name"
                        class="form-control"
                        value="{{ old('name', $editedSwimmer?->name) }}"
                        data-swimmer-name
                    >
                </div>

                <div>
                    <label class="form-label" for="swimmer_birth_year">سنة الميلاد</label>
                    <input
                        type="number"
                        id="swimmer_birth_year"
                        name="birth_year"
                        class="form-control"
                        min="1950"
                        max="{{ $currentYear }}"
                        value="{{ $initialBirthYear }}"
                        data-swimmer-birth-year
                    >
                </div>

                <div>
                    <label class="form-label" for="swimmer_age">السن</label>
                    <input type="text" id="swimmer_age" class="form-control" readonly data-swimmer-age>
                </div>

                <div>
                    <label class="form-label" for="swimmer_father_phone">رقم الأب</label>
                    <input
                        type="text"
                        id="swimmer_father_phone"
                        name="father_phone"
                        class="form-control"
                        value="{{ old('father_phone', $editedSwimmer?->father_phone) }}"
                        data-swimmer-father-phone
                    >
                </div>

                <div>
                    <label class="form-label" for="swimmer_mother_phone">رقم الأم</label>
                    <input
                        type="text"
                        id="swimmer_mother_phone"
                        name="mother_phone"
                        class="form-control"
                        value="{{ old('mother_phone', $editedSwimmer?->mother_phone) }}"
                        data-swimmer-mother-phone
                    >
                </div>

                <div>
                    <label class="form-label" for="swimmer_group">المجموعة</label>
                    <select id="swimmer_group" name="training_group_id" class="form-select" data-swimmer-group>
                        <option value=""></option>
                        @foreach($trainingGroups as $trainingGroup)
                            <option
                                value="{{ $trainingGroup->id }}"
                                @selected($selectedGroupId === (string) $trainingGroup->id)
                            >
                                {{ $trainingGroup->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="form-label" for="swimmer_subscription_start_date">تاريخ بداية الاشتراك</label>
                    <input
                        type="date"
                        id="swimmer_subscription_start_date"
                        name="subscription_start_date"
                        class="form-control"
                        value="{{ old('subscription_start_date', optional($editedSwimmer?->subscription_start_date)->toDateString() ?? now()->toDateString()) }}"
                        data-swimmer-start-date
                    >
                </div>

                <div>
                    <label class="form-label" for="swimmer_subscription_end_date">تاريخ نهاية الاشتراك</label>
                    <input
                        type="date"
                        id="swimmer_subscription_end_date"
                        name="subscription_end_date"
                        class="form-control"
                        value="{{ old('subscription_end_date', optional($editedSwimmer?->subscription_end_date)->toDateString()) }}"
                        readonly
                        data-swimmer-end-date
                    >
                </div>

                <div>
                    <label class="form-label" for="swimmer_group_price">سعر المجموعة</label>
                    <input
                        type="number"
                        id="swimmer_group_price"
                        name="group_price"
                        class="form-control"
                        min="0"
                        step="0.01"
                        value="{{ $initialPrice }}"
                        data-swimmer-price
                    >
                </div>

                <div>
                    <label class="form-label" for="swimmer_amount_paid">المدفوع</label>
                    <input
                        type="number"
                        id="swimmer_amount_paid"
                        name="amount_paid"
                        class="form-control"
                        min="0"
                        step="0.01"
                        value="{{ $initialPaid }}"
                        data-swimmer-paid
                    >
                </div>

                <div>
                    <label class="form-label" for="swimmer_remaining_amount">المتبقي</label>
                    <input type="number" id="swimmer_remaining_amount" class="form-control" readonly data-swimmer-remaining>
                </div>

                <div class="d-flex align-items-end">
                    <div class="form-check">
                        <input
                            type="hidden"
                            name="exclude_from_financial_totals"
                            value="0"
                        >
                        <input
                            type="checkbox"
                            id="swimmer_exclude_from_financial_totals"
                            name="exclude_from_financial_totals"
                            class="form-check-input"
                            value="1"
                            @checked((bool) $excludeFromFinancialTotals)
                        >
                        <label class="form-check-label" for="swimmer_exclude_from_financial_totals">
                            غير محسوب
                        </label>
                        <div class="form-help-text">فعّلها إذا كان هذا الاشتراك مدفوعًا من قبل ولا يجب دخوله في الإجماليات.</div>
                    </div>
                </div>

                <div class="form-actions-row trainer-form-actions swimmers-actions-row">
                    <button type="submit" class="btn primary-btn">{{ $editedSwimmer ? 'حفظ' : 'إضافة' }}</button>
                    <a href="{{ route('swimmers.index') }}" class="btn action-btn">إلغاء</a>
                </div>
            </form>
        </section>

        <section class="panel-card table-card wide-card trainer-table-card swimmer-table-card">
            <h2 class="section-title">جدول السباحين</h2>
            @if($swimmers->isEmpty())
                <div class="empty-state">لا يوجد سباحون مسجلون حتى الآن.</div>
            @else
                <div class="table-responsive">
                    <table class="table align-middle app-table">
                        <thead>
                            <tr>
                                <th>السباح</th>
                                <th>الباركود</th>
                                <th>سنة الميلاد</th>
                                <th>السن</th>
                                <th>رقم الأب</th>
                                <th>رقم الأم</th>
                                <th>المجموعة</th>
                                <th>بداية الاشتراك</th>
                                <th>نهاية الاشتراك</th>
                                <th>الحساب</th>
                                <th>السعر</th>
                                <th>المدفوع</th>
                                <th>المتبقي</th>
                                <th class="table-actions">الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($swimmers as $swimmer)
                                @php($playerPhoto = $swimmer->playerPhotoFile())
                                <tr>
                                    <td>
                                        <div class="swimmer-name-cell">
                                            @if($playerPhoto)
                                                <img src="{{ $playerPhoto->imageUrl() }}" alt="{{ $swimmer->name }}" class="swimmer-avatar-image">
                                            @else
                                                <span class="trainer-avatar"><i class="bi bi-person-arms-up"></i></span>
                                            @endif
                                            <span>{{ $swimmer->name }}</span>
                                        </div>
                                    </td>
                                    <td class="swimmer-barcode-cell">{{ $swimmer->barcode }}</td>
                                    <td>{{ $swimmer->birth_year }}</td>
                                    <td>{{ $swimmer->age() }}</td>
                                    <td>{{ $swimmer->father_phone }}</td>
                                    <td>{{ $swimmer->mother_phone }}</td>
                                    <td>{{ $swimmer->trainingGroup->name }}</td>
                                    <td>{{ optional($swimmer->subscription_start_date)->format('Y-m-d') }}</td>
                                    <td>{{ optional($swimmer->subscription_end_date)->format('Y-m-d') }}</td>
                                    <td>
                                        @if($swimmer->exclude_from_financial_totals)
                                            <span class="badge text-bg-warning">غير محسوب</span>
                                        @else
                                            <span class="badge text-bg-success">محسوب</span>
                                        @endif
                                    </td>
                                    <td>{{ $formatNumber($swimmer->group_price) }}</td>
                                    <td>{{ $formatNumber($swimmer->amount_paid) }}</td>
                                    <td>{{ $formatNumber($swimmer->remaining_amount) }}</td>
                                    <td class="table-actions">
                                        <div class="table-action-group">
                                            <a href="{{ route('swimmers.files.index', $swimmer) }}" class="btn action-btn">ملفات اللاعب</a>
                                            <a href="{{ route('swimmers.edit', $swimmer) }}#swimmer-form-panel" class="btn action-btn">تعديل</a>
                                            <form action="{{ route('swimmers.destroy', $swimmer) }}" method="POST" class="inline-form">
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
