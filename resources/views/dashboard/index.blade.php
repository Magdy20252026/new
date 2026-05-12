@extends('layouts.dashboard')

@section('content')
    <section class="hero-panel">
        <div class="hero-panel-content">
            <div class="hero-kicker">لوحة التحكم الرئيسية</div>
            <h1 class="hero-title">مرحبًا، {{ $authUser->username }}</h1>
            <div class="hero-meta">
                <span>{{ $authUser->roleLabel() }}</span>
                <span class="hero-divider"></span>
                <span>{{ $currentBranch?->name }}</span>
            </div>
        </div>
    </section>

    <section class="stats-grid">
        <div class="stats-grid-header">
            <h2 class="section-title">الإحصائيات</h2>
            <p class="stats-grid-text">ملخص سريع لبيانات لوحة التحكم الحالية.</p>
        </div>

        @foreach($stats as $stat)
            <div class="stat-card">
                <div class="stat-card-icon">
                    <i class="bi {{ $stat['icon'] }}"></i>
                </div>
                <div class="stat-card-label">{{ $stat['label'] }}</div>
                <div class="stat-card-value">{{ $stat['value'] }}</div>
            </div>
        @endforeach
    </section>

    @if($authUser->isManager())
        <section class="quick-actions-panel">
            <div class="stats-grid-header">
                <h2 class="section-title">إعدادات سريعة</h2>
                <p class="stats-grid-text">وصول مباشر لأهم إعدادات القبض الخاصة بالمدربين.</p>
            </div>

            <div class="quick-actions-grid">
                <a href="{{ route('trainer-payment-week.edit') }}" class="quick-action-card">
                    <div class="quick-action-icon">
                        <i class="bi bi-calendar-week-fill"></i>
                    </div>
                    <div class="quick-action-title">بداية اسبوع قبض المدربين</div>
                    <div class="quick-action-text">
                        من {{ $trainerPaymentWeek['start_label'] }} إلى {{ $trainerPaymentWeek['end_label'] }}
                    </div>
                </a>
            </div>
        </section>
    @endif

@endsection
