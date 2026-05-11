<!DOCTYPE html>
<html lang="ar" dir="rtl" data-theme="{{ $themeMode }}">
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
    <button type="button" class="theme-toggle-btn" id="themeToggle" aria-label="تبديل الوضع">
        <i class="bi bi-moon-stars-fill icon-dark"></i>
        <i class="bi bi-brightness-high-fill icon-light"></i>
    </button>

    <main class="login-page">
        <div class="login-card">
            <div class="login-brand">
                <div class="brand-circle">{{ mb_substr($siteSettings['site_name'], 0, 1) }}</div>
                <h1>{{ $siteSettings['site_name'] }}</h1>
            </div>

            @if($setupError)
                <div class="alert alert-danger login-alert">{{ $setupError }}</div>
            @elseif($errors->any())
                <div class="alert alert-danger login-alert">{{ $errors->first() }}</div>
            @endif

            <form action="{{ route('login.submit') }}" method="POST" class="login-form">
                @csrf
                <div class="form-row">
                    <label for="branch_id" class="form-label">الفرع</label>
                    <div class="input-shell">
                        <i class="bi bi-building"></i>
                        <select name="branch_id" id="branch_id" class="form-select @error('branch_id') is-invalid @enderror" @disabled($setupError)>
                            <option value="">اختر الفرع</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" @selected(old('branch_id') == $branch->id)>{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <label for="username" class="form-label">اسم المستخدم</label>
                    <div class="input-shell">
                        <i class="bi bi-person-fill"></i>
                        <input type="text" name="username" id="username" class="form-control @error('username') is-invalid @enderror" value="{{ old('username') }}" autocomplete="username" @disabled($setupError)>
                    </div>
                </div>

                <div class="form-row">
                    <label for="password" class="form-label">كلمة السر</label>
                    <div class="input-shell">
                        <i class="bi bi-shield-lock-fill"></i>
                        <input type="password" name="password" id="password" class="form-control @error('password') is-invalid @enderror" autocomplete="current-password" @disabled($setupError)>
                        <button type="button" class="input-action" id="togglePassword" @disabled($setupError)><i class="bi bi-eye-fill" id="togglePasswordIcon"></i></button>
                    </div>
                </div>

                <button type="submit" class="btn login-submit" @disabled($setupError)>دخول</button>
            </form>
        </div>
    </main>

    <script src="{{ asset('assets/js/login.js') }}"></script>
</body>
</html>
