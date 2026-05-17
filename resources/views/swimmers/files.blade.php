@extends('layouts.dashboard')

@section('content')
    <div class="stacked-content">
        <section class="panel-card trainer-files-header-card">
            <div class="trainer-files-header">
                <div>
                    <h2 class="section-title mb-2">ملفات {{ $swimmer->name }}</h2>
                    <div class="form-help-text mt-0">يمكنك إضافة أو تعديل أو حذف صورة السباح وشهادة الميلاد والتقرير الطبي وكارنية الاتحاد.</div>
                </div>
                <a href="{{ route('swimmers.index') }}" class="btn action-btn">العودة للسباحين</a>
            </div>
        </section>

        <section class="panel-card trainer-form-card swimmer-files-form-card">
            <h2 class="section-title">{{ $editedSwimmerFile ? 'تعديل ملف السباح' : 'إضافة ملفات للسباح' }}</h2>
            <form
                action="{{ $editedSwimmerFile ? route('swimmers.files.update', [$swimmer, $editedSwimmerFile]) : route('swimmers.files.store', $swimmer) }}"
                method="POST"
                enctype="multipart/form-data"
                class="form-grid top-form-grid swimmer-files-form"
                data-swimmer-files-form
            >
                @csrf
                @if($editedSwimmerFile)
                    @method('PUT')
                @endif

                <div>
                    <label class="form-label" for="swimmer_file_type">نوع الملف</label>
                    <select id="swimmer_file_type" name="type" class="form-select" data-swimmer-file-type>
                        @foreach($fileTypeLabels as $value => $label)
                            <option value="{{ $value }}" @selected(old('type', $editedSwimmerFile?->type) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="form-label" for="swimmer_file_title">اسم الملف</label>
                    <input
                        type="text"
                        id="swimmer_file_title"
                        name="title"
                        class="form-control"
                        value="{{ old('title', $editedSwimmerFile?->title) }}"
                    >
                </div>

                @if($editedSwimmerFile)
                    <div>
                        <label class="form-label" for="swimmer_file_image">استبدال الصورة</label>
                        <input type="file" id="swimmer_file_image" name="image" class="form-control" accept="image/*">
                        <div class="form-help-text">اترك الحقل فارغًا إذا كنت تريد تعديل البيانات فقط.</div>
                    </div>

                    <div class="logo-preview-card trainer-file-preview-card">
                        <div class="logo-preview-label">الملف الحالي</div>
                        <div class="settings-logo-box trainer-file-preview-box">
                            <img src="{{ $editedSwimmerFile->imageUrl() }}" alt="{{ $editedSwimmerFile->title }}" class="settings-logo-preview trainer-file-preview-image">
                        </div>
                    </div>
                @else
                    <div>
                        <label class="form-label" for="swimmer_file_images">الصور</label>
                        <input
                            type="file"
                            id="swimmer_file_images"
                            name="images[]"
                            class="form-control"
                            accept="image/*"
                            data-swimmer-file-input
                        >
                        <div class="form-help-text" data-swimmer-file-help>يمكنك رفع جميع أنواع الصور، وللتقرير الطبي يمكنك اختيار أكثر من صورة.</div>
                    </div>
                @endif

                <div class="form-actions-row trainer-form-actions swimmers-actions-row">
                    <button type="submit" class="btn primary-btn">{{ $editedSwimmerFile ? 'حفظ التعديلات' : 'رفع الملف' }}</button>
                    <a href="{{ route('swimmers.files.index', $swimmer) }}" class="btn action-btn">إلغاء</a>
                </div>
            </form>
        </section>

        @foreach($groupedSwimmerFiles as $type => $files)
            <section class="panel-card table-card trainer-table-card">
                <h2 class="section-title">{{ $fileTypeLabels[$type] }}</h2>

                @if($files->isEmpty())
                    <div class="empty-state">لا توجد ملفات مرفوعة في هذا القسم.</div>
                @else
                    <div class="trainer-files-grid">
                        @foreach($files as $swimmerFile)
                            <article class="trainer-file-card">
                                <a href="{{ $swimmerFile->imageUrl() }}" target="_blank" rel="noopener noreferrer" class="trainer-file-image-link">
                                    <img src="{{ $swimmerFile->imageUrl() }}" alt="{{ $swimmerFile->title }}" class="trainer-file-image">
                                </a>

                                <div class="trainer-file-body">
                                    <div class="trainer-file-title">{{ $swimmerFile->title }}</div>
                                    <div class="form-help-text mt-0">{{ $swimmerFile->typeLabel() }}</div>
                                    <div class="table-action-group trainer-file-actions">
                                        <a href="{{ $swimmerFile->imageUrl() }}" target="_blank" rel="noopener noreferrer" class="btn action-btn">عرض</a>
                                        <a href="{{ route('swimmers.files.edit', [$swimmer, $swimmerFile]) }}" class="btn action-btn">تعديل</a>
                                        <form action="{{ route('swimmers.files.destroy', [$swimmer, $swimmerFile]) }}" method="POST" class="inline-form">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn action-btn danger-btn">حذف</button>
                                        </form>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @endif
            </section>
        @endforeach
    </div>
@endsection
