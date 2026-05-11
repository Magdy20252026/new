@extends('layouts.dashboard')

@section('content')
    <section class="panel-card form-panel settings-panel">
        <h2 class="section-title">إعدادات الموقع</h2>

        <form action="{{ route('site-settings.update') }}" method="POST" enctype="multipart/form-data" class="form-grid settings-form">
            @csrf
            @method('PUT')

            <div>
                <label class="form-label" for="site_name">اسم الأكاديمية</label>
                <input
                    type="text"
                    id="site_name"
                    name="site_name"
                    class="form-control"
                    value="{{ old('site_name', $siteSettings['site_name']) }}"
                >
            </div>

            <div>
                <label class="form-label" for="site_logo">شعار الأكاديمية</label>
                <input
                    type="file"
                    id="site_logo"
                    name="site_logo"
                    class="form-control"
                    accept="image/*"
                >
                <div class="form-help-text">يمكن رفع أي صورة للشعار بأي أبعاد، وسيتم عرضها تلقائيًا بشكل مناسب.</div>
            </div>

            <div class="logo-preview-card">
                <div class="logo-preview-label">الشعار الحالي</div>
                <div class="settings-logo-box">
                    <img src="{{ $siteSettings['site_logo'] }}" alt="شعار الأكاديمية" class="settings-logo-preview">
                </div>
            </div>

            <div class="form-actions-row">
                <button type="submit" class="btn primary-btn">حفظ الإعدادات</button>
            </div>
        </form>
    </section>
@endsection
