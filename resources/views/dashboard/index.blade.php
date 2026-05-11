@extends('layouts.dashboard')

@section('content')
    <section class="hero-panel">
        <div class="hero-panel-content">
            <div class="hero-kicker">لوحة التحكم الرئيسية</div>
            <h1 class="hero-title">مرحبًا، {{ $authUser->name }}</h1>
            <div class="hero-meta">
                <span>{{ $authUser->roleLabel() }}</span>
                <span class="hero-divider"></span>
                <span>{{ $currentBranch?->name }}</span>
            </div>
        </div>
    </section>

    <section class="stats-grid">
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

    <section class="quick-actions-panel">
        <h2 class="section-title">روابط سريعة</h2>
        <div class="quick-actions-grid">
            <a href="{{ route('branches.index') }}" class="quick-action-card {{ $authUser->isManager() ? '' : 'disabled' }}">
                <span class="quick-action-icon"><i class="bi bi-diagram-3-fill"></i></span>
                <span class="quick-action-title">الفروع</span>
                <span class="quick-action-text">فتح إدارة الفروع</span>
            </a>
            <a href="{{ route('users.index') }}" class="quick-action-card {{ $authUser->isManager() ? '' : 'disabled' }}">
                <span class="quick-action-icon"><i class="bi bi-people-fill"></i></span>
                <span class="quick-action-title">المستخدمين</span>
                <span class="quick-action-text">فتح إدارة المستخدمين</span>
            </a>
        </div>
    </section>
@endsection
