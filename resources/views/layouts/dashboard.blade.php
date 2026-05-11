<!DOCTYPE html>
<html lang="ar" dir="rtl" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $siteSettings['site_name'] ?? 'لوحة التحكم' }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ asset('assets/css/dashboard.css') }}" rel="stylesheet">
</head>
<body>
    <div class="dashboard-shell" id="dashboardApp">
        <div class="dashboard-overlay" id="sidebarBackdrop"></div>

        <aside class="dashboard-sidebar" id="dashboardSidebar">
            <div class="sidebar-head">
                <div class="academy-brand text-center">
                    <h2 class="academy-name">{{ $siteSettings['site_name'] ?? 'اسم الأكاديمية' }}</h2>

                    <div class="academy-logo-box">
                        <img
                            src="{{ $siteSettings['site_logo'] ?? asset('assets/images/logo.png') }}"
                            alt="Logo"
                            class="academy-logo"
                        >
                    </div>

                    <div class="academy-user-card">
                        <div class="academy-user-name">{{ $user['name'] ?? 'اسم المستخدم' }}</div>
                        <div class="academy-user-role">{{ $user['role'] ?? 'الصلاحية' }}</div>
                    </div>
                </div>
            </div>

            <div class="sidebar-body">
                <nav class="sidebar-nav">
                    @foreach($menuItems as $item)
                        <a href="{{ $item['route'] }}"
                           class="sidebar-nav-link {{ $item['active'] ? 'active' : '' }}">
                            <span class="sidebar-nav-icon">
                                <i class="bi {{ $item['icon'] }}"></i>
                            </span>
                            <span class="sidebar-nav-text">{{ $item['title'] }}</span>
                        </a>
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
                        <div class="topbar-title">لوحة التحكم</div>
                        <div class="topbar-subtitle">{{ $siteSettings['site_name'] ?? 'الأكاديمية' }}</div>
                    </div>
                </div>

                <div class="topbar-center">
                    <div class="branch-badge">
                        <i class="bi bi-geo-alt-fill"></i>
                        <span>{{ $currentBranch['name'] ?? 'الفرع الحالي' }}</span>
                    </div>
                </div>

                <div class="topbar-left">
                    <button type="button" class="topbar-btn" id="themeToggle" aria-label="تبديل الوضع">
                        <i class="bi bi-moon-stars-fill icon-dark"></i>
                        <i class="bi bi-brightness-high-fill icon-light"></i>
                    </button>
                </div>
            </header>

            <div class="breadcrumb-bar">
                <div class="breadcrumb-item active">الرئيسية</div>
                <i class="bi bi-chevron-left breadcrumb-separator"></i>
                <div class="breadcrumb-item">لوحة التحكم</div>
            </div>

            <main class="dashboard-content">
                @yield('content')
            </main>
        </div>
    </div>

    <script src="{{ asset('assets/js/dashboard.js') }}"></script>
</body>
</html>