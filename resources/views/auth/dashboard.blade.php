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
    <div class="dashboard-app" id="dashboardApp">
        <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

        <aside class="dashboard-sidebar" id="dashboardSidebar">
            <div class="sidebar-top">
                <div class="academy-block text-center">
                    <h2 class="academy-name">{{ $siteSettings['site_name'] ?? 'اسم الأكاديمية' }}</h2>

                    <div class="academy-logo-wrap">
                        <img src="{{ $siteSettings['site_logo'] ?? asset('assets/images/logo.png') }}" alt="Logo" class="academy-logo">
                    </div>

                    <div class="user-box">
                        <div class="user-name">{{ $user['name'] ?? 'اسم المستخدم' }}</div>
                        <div class="user-role">{{ $user['role'] ?? 'الصلاحية' }}</div>
                    </div>
                </div>
            </div>

            <div class="sidebar-menu">
                @foreach($menuItems as $item)
                    <a href="#" class="sidebar-link">
                        <span>{{ $item }}</span>
                    </a>
                @endforeach
            </div>
        </aside>

        <div class="dashboard-main">
            <header class="dashboard-header">
                <div class="header-right">
                    <button class="menu-toggle-btn" id="menuToggle" type="button" aria-label="فتح وإغلاق القائمة">
                        <i class="bi bi-list"></i>
                    </button>
                    <div class="header-title">لوحة التحكم</div>
                </div>

                <div class="header-left">
                    <button type="button" class="theme-toggle-btn" id="themeToggle" aria-label="تبديل الوضع">
                        <i class="bi bi-moon-stars-fill icon-dark"></i>
                        <i class="bi bi-brightness-high-fill icon-light"></i>
                    </button>
                </div>
            </header>

            <main class="dashboard-content">
                @yield('content')
            </main>
        </div>
    </div>

    <script src="{{ asset('assets/js/dashboard.js') }}"></script>
</body>
</html>