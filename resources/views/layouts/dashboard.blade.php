<!DOCTYPE html>
<html lang="ar" dir="rtl" data-theme="{{ $themeMode }}">
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
                <div class="brand-mark">{{ mb_substr($siteSettings['site_name'], 0, 1) }}</div>
                <div>
                    <h1 class="sidebar-title">{{ $siteSettings['site_name'] }}</h1>
                    <div class="sidebar-user">{{ $authUser->name }} · {{ $authUser->roleLabel() }}</div>
                </div>
            </div>

            <nav class="sidebar-nav">
                @foreach($menuItems as $item)
                    <a href="{{ $item['route'] }}" class="sidebar-nav-link {{ $item['active'] ? 'active' : '' }}">
                        <i class="bi {{ $item['icon'] }}"></i>
                        <span>{{ $item['title'] }}</span>
                    </a>
                @endforeach
            </nav>
        </aside>

        <div class="dashboard-main">
            <header class="dashboard-topbar">
                <div class="topbar-section topbar-right">
                    <button class="topbar-btn" id="menuToggle" type="button" aria-label="القائمة">
                        <i class="bi bi-list"></i>
                    </button>
                    <div>
                        <div class="page-title">{{ $pageTitle ?? 'لوحة التحكم' }}</div>
                        <div class="page-branch">{{ $currentBranch?->name }}</div>
                    </div>
                </div>

                <div class="topbar-section topbar-center">
                    @if($accessibleBranches->count() > 1)
                        <form action="{{ route('branch.switch') }}" method="POST" class="branch-switch-form">
                            @csrf
                            <select class="form-select branch-switch-input" name="branch_id" onchange="this.form.submit()">
                                @foreach($accessibleBranches as $branchOption)
                                    <option value="{{ $branchOption->id }}" @selected($currentBranch?->id === $branchOption->id)>{{ $branchOption->name }}</option>
                                @endforeach
                            </select>
                        </form>
                    @else
                        <div class="branch-chip"><i class="bi bi-building"></i><span>{{ $currentBranch?->name }}</span></div>
                    @endif
                </div>

                <div class="topbar-section topbar-left">
                    <button type="button" class="topbar-btn" id="themeToggle" aria-label="تبديل الوضع">
                        <i class="bi bi-moon-stars-fill icon-dark"></i>
                        <i class="bi bi-brightness-high-fill icon-light"></i>
                    </button>
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="topbar-btn topbar-btn-danger" aria-label="تسجيل الخروج">
                            <i class="bi bi-box-arrow-right"></i>
                        </button>
                    </form>
                </div>
            </header>

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
