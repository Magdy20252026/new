@extends('layouts.dashboard')

@section('content')
    <section class="hero-panel">
        <div class="hero-panel-content">
            <div class="hero-kicker">لوحة التحكم الرئيسية</div>
            <h1 class="hero-title">مرحبًا، {{ $user['name'] ?? 'اسم المستخدم' }}</h1>
            <div class="hero-meta">
                <span>{{ $user['role'] ?? 'الصلاحية' }}</span>
                <span class="hero-divider"></span>
                <span>{{ $currentBranch['name'] ?? 'الفرع الحالي' }}</span>
            </div>
        </div>
    </section>

    <section class="stats-grid stats-grid-3">
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
@endsection