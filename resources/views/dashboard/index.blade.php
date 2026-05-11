@extends('layouts.dashboard')

@section('content')
    <section class="stats-grid">
        @foreach($stats as $stat)
            <article class="stat-card">
                <div class="stat-icon"><i class="bi {{ $stat['icon'] }}"></i></div>
                <div class="stat-label">{{ $stat['label'] }}</div>
                <div class="stat-value">{{ $stat['value'] }}</div>
            </article>
        @endforeach
    </section>

    <section class="panel-card quick-grid">
        <a href="{{ route('branches.index') }}" class="quick-link {{ $authUser->isManager() ? '' : 'disabled' }}">
            <i class="bi bi-diagram-3-fill"></i>
            <span>الفروع</span>
        </a>
        <a href="{{ route('users.index') }}" class="quick-link {{ $authUser->isManager() ? '' : 'disabled' }}">
            <i class="bi bi-people-fill"></i>
            <span>المستخدمين</span>
        </a>
    </section>
@endsection
