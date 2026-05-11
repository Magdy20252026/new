<!DOCTYPE html>
<html lang="ar" dir="rtl" data-theme="{{ $themeMode }}" data-bs-theme="{{ $themeMode }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $pageTitle ?? 'لوحة التحكم' }} - {{ $siteSettings['site_name'] }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ asset('assets/css/dashboard.css') }}" rel="stylesheet">
</head>
<body data-theme-url="{{ route('theme.update') }}">
    <div class="dashboard-shell" id="dashboardApp">
        <div class="dashboard-overlay" id="sidebarBackdrop"></div>

        <aside class="dashboard-sidebar" id="dashboardSidebar">
            <div class="sidebar-head">
                <div class="academy-brand text-center">
                    <h2 class="academy-name">{{ $siteSettings['site_name'] }}</h2>

                    <div class="academy-logo-box">
                        <img
                            src="{{ $siteSettings['site_logo'] ?? asset('assets/images/logo.png') }}"
                            alt="Logo"
                            class="academy-logo"
                        >
                    </div>

                    <div class="academy-user-card">
                        <div class="academy-user-name">{{ $authUser->username }}</div>
                        <div class="academy-user-role">{{ $authUser->roleLabel() }}</div>
                    </div>
                </div>
            </div>

            <div class="sidebar-body">
                <nav class="sidebar-nav">
                    @foreach($menuItems as $item)
                        @if($item['type'] === 'form')
                            <form action="{{ $item['route'] }}" method="POST" class="sidebar-nav-form">
                                @csrf
                                <button type="submit" class="sidebar-nav-link sidebar-nav-button logout-link">
                                    <span class="sidebar-nav-icon">
                                        <i class="bi {{ $item['icon'] }}"></i>
                                    </span>
                                    <span class="sidebar-nav-text">{{ $item['title'] }}</span>
                                </button>
                            </form>
                        @elseif($item['available'])
                            <a href="{{ $item['route'] }}"
                               class="sidebar-nav-link {{ $item['active'] ? 'active' : '' }}">
                                <span class="sidebar-nav-icon">
                                    <i class="bi {{ $item['icon'] }}"></i>
                                </span>
                                <span class="sidebar-nav-text">{{ $item['title'] }}</span>
                            </a>
                        @else
                            <button type="button" class="sidebar-nav-link sidebar-nav-button is-disabled" disabled aria-disabled="true">
                                <span class="sidebar-nav-icon">
                                    <i class="bi {{ $item['icon'] }}"></i>
                                </span>
                                <span class="sidebar-nav-text">{{ $item['title'] }}</span>
                            </button>
                        @endif
                    @endforeach
                </nav>
            </div>
        </aside>

        <div class="dashboard-main">
            <header class="dashboard-topbar">
                <div class="topbar-right">
                    <button class="topbar-btn" id="menuToggle" type="button" aria-label="فتح وإغلاق القائمة">
                        <i class="bi bi-list"></i>
                    </button>

                    <div class="topbar-title-wrap">
                        <div class="topbar-title">{{ $pageTitle ?? 'لوحة التحكم' }}</div>
                        <div class="topbar-site-name">{{ $siteSettings['site_name'] }}</div>
                    </div>
                </div>

                <div class="topbar-center">
                    <div class="branch-badge">
                        <i class="bi bi-geo-alt-fill"></i>
                        <span>{{ $currentBranch?->name }}</span>
                    </div>
                </div>

                <div class="topbar-left">
                    @if(($pageTitle ?? 'الرئيسية') !== 'الرئيسية')
                        <a href="{{ route('dashboard') }}" class="topbar-home-link">
                            <i class="bi bi-house-door-fill"></i>
                            <span>الرئيسية</span>
                        </a>
                    @endif

                    <button type="button" class="topbar-btn" id="themeToggle" aria-label="تبديل الوضع">
                        <i class="bi bi-moon-stars-fill icon-dark"></i>
                        <i class="bi bi-brightness-high-fill icon-light"></i>
                    </button>
                </div>
            </header>

            <div class="breadcrumb-bar">
                <a href="{{ route('dashboard') }}" class="breadcrumb-item breadcrumb-link">لوحة التحكم</a>
                <i class="bi bi-chevron-left breadcrumb-separator"></i>
                <div class="breadcrumb-item active">{{ $pageTitle ?? 'الرئيسية' }}</div>
            </div>

            <main class="dashboard-content">
                @if(session('status'))
                    <div class="alert alert-success panel-alert">{{ session('status') }}</div>
                @endif

                @if($errors->any())
                    <div class="alert alert-danger panel-alert">{{ $errors->first() }}</div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    <script src="{{ asset('assets/js/dashboard.js') }}"></script>
</body>
</html>
