<!DOCTYPE html>
<html lang="ar" dir="rtl" data-theme="{{ $themeMode }}" data-bs-theme="{{ $themeMode }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول - {{ $siteSettings['site_name'] }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ asset('assets/css/login.css') }}" rel="stylesheet">
</head>
<body>
    <div class="water-bg">
        <div class="wave wave-1"></div>
        <div class="wave wave-2"></div>
        <div class="wave wave-3"></div>
        <div class="glow glow-1"></div>
        <div class="glow glow-2"></div>
        <div class="bubbles bubbles-1"></div>
        <div class="bubbles bubbles-2"></div>
    </div>

    <div class="theme-toggle-wrapper">
        <button type="button" class="theme-toggle-btn" id="themeToggle" aria-label="تبديل الوضع">
            <i class="bi bi-moon-stars-fill icon-dark"></i>
            <i class="bi bi-brightness-high-fill icon-light"></i>
        </button>
    </div>

    <main class="login-page">
        <div class="container px-3 px-md-4">
            <div class="row justify-content-center align-items-center min-vh-100 py-4">
                <div class="col-12 col-md-9 col-lg-7 col-xl-5">
                    <div class="swim-login-card">
                        <div class="swim-card-topline"></div>

                        <div class="swim-login-inner">
                            <div class="brand-area text-center">
                                <div class="logo-shell">
                                    <div class="logo-ring"></div>
                                    <img
                                        src="{{ $siteSettings['site_logo'] ?? asset('assets/images/logo.png') }}"
                                        alt="Logo"
                                        class="brand-logo"
                                    >
                                </div>

                                <h1 class="brand-title">
                                    {{ $siteSettings['site_name'] }}
                                </h1>
                            </div>

                            @if($setupError)
                                <div class="alert alert-danger mb-3">{{ $setupError }}</div>
                            @elseif($errors->any())
                                <div class="alert alert-danger mb-3">{{ $errors->first() }}</div>
                            @endif

                            <form action="{{ route('login.submit') }}" method="POST">
                                @csrf

                                <div class="mb-3">
                                    <label for="branch_id" class="form-label">الفرع</label>
                                    <div class="input-group swim-input-group">
                                        <span class="input-group-text">
                                            <i class="bi bi-building"></i>
                                        </span>
                                        <select
                                            name="branch_id"
                                            id="branch_id"
                                            class="form-select branch-select @error('branch_id') is-invalid @enderror"
                                            @disabled($setupError)
                                        >
                                            <option value="">اختر الفرع</option>
                                            @foreach($branches as $branch)
                                                <option value="{{ $branch->id }}" @selected(old('branch_id') == $branch->id)>
                                                    {{ $branch->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    @error('branch_id')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="username" class="form-label">اسم المستخدم</label>
                                    <div class="input-group swim-input-group">
                                        <span class="input-group-text">
                                            <i class="bi bi-person-fill"></i>
                                        </span>
                                        <input
                                            type="text"
                                            name="username"
                                            id="username"
                                            class="form-control @error('username') is-invalid @enderror"
                                            value="{{ old('username') }}"
                                            autocomplete="username"
                                            @disabled($setupError)
                                        >
                                    </div>
                                    @error('username')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-4">
                                    <label for="password" class="form-label">كلمة السر</label>
                                    <div class="input-group swim-input-group">
                                        <span class="input-group-text">
                                            <i class="bi bi-shield-lock-fill"></i>
                                        </span>
                                        <input
                                            type="password"
                                            name="password"
                                            id="password"
                                            class="form-control @error('password') is-invalid @enderror"
                                            autocomplete="current-password"
                                            @disabled($setupError)
                                        >
                                        <button class="input-group-text password-toggle" type="button" id="togglePassword" @disabled($setupError)>
                                            <i class="bi bi-eye-fill" id="togglePasswordIcon"></i>
                                        </button>
                                    </div>
                                    @error('password')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="d-grid">
                                    <button type="submit" class="btn swim-login-btn" @disabled($setupError)>
                                        <i class="bi bi-water me-2"></i>
                                        <span>تسجيل الدخول</span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="copyright-text text-center">
                        © {{ date('Y') }} {{ $siteSettings['site_name'] }}
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="{{ asset('assets/js/login.js') }}"></script>
</body>
</html>
