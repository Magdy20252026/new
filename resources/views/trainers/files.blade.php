@extends('layouts.dashboard')

@section('content')
    <div class="stacked-content">
        <section class="panel-card trainer-files-header-card">
            <div class="trainer-files-header">
                <div>
                    <h2 class="section-title mb-2">ملفات {{ $trainer->name }}</h2>
                    <div class="form-help-text mt-0">يمكنك رفع صور المدرب وتسميتها ثم عرضها أو تعديلها أو حذفها.</div>
                </div>
                <a href="{{ route('trainers.index') }}" class="btn action-btn">العودة للمدربين</a>
            </div>
        </section>

        <section class="panel-card trainer-form-card">
            <h2 class="section-title">{{ $editedTrainerFile ? 'تعديل ملف المدرب' : 'إضافة ملف للمدرب' }}</h2>
            <form
                action="{{ $editedTrainerFile ? route('trainers.files.update', [$trainer, $editedTrainerFile]) : route('trainers.files.store', $trainer) }}"
                method="POST"
                enctype="multipart/form-data"
                class="form-grid top-form-grid trainer-files-form"
            >
                @csrf
                @if($editedTrainerFile)
                    @method('PUT')
                @endif

                <div>
                    <label class="form-label" for="trainer_file_title">اسم الصورة</label>
                    <input
                        type="text"
                        id="trainer_file_title"
                        name="title"
                        class="form-control"
                        value="{{ old('title', $editedTrainerFile?->title) }}"
                    >
                </div>

                <div>
                    <label class="form-label" for="trainer_file_image">صورة الملف</label>
                    <input
                        type="file"
                        id="trainer_file_image"
                        name="image"
                        class="form-control"
                        accept="image/*"
                    >
                    <div class="form-help-text">
                        {{ $editedTrainerFile ? 'اترك الحقل فارغًا إذا كنت تريد تعديل الاسم فقط.' : 'ارفع صورة للمدرب مع الاسم الذي تريد عرضه.' }}
                    </div>
                </div>

                @if($editedTrainerFile)
                    <div class="logo-preview-card trainer-file-preview-card">
                        <div class="logo-preview-label">الصورة الحالية</div>
                        <div class="settings-logo-box trainer-file-preview-box">
                            <img src="{{ $editedTrainerFile->imageUrl() }}" alt="{{ $editedTrainerFile->title }}" class="settings-logo-preview trainer-file-preview-image">
                        </div>
                    </div>
                @endif

                <div class="form-actions-row trainer-form-actions">
                    <button type="submit" class="btn primary-btn">{{ $editedTrainerFile ? 'حفظ التعديلات' : 'رفع الملف' }}</button>
                    @if($editedTrainerFile)
                        <a href="{{ route('trainers.files.index', $trainer) }}" class="btn action-btn">إلغاء</a>
                    @endif
                </div>
            </form>
        </section>

        <section class="panel-card table-card trainer-table-card">
            <h2 class="section-title">ملفات المدرب</h2>

            @if($trainerFiles->isEmpty())
                <div class="empty-state">لا توجد ملفات مرفوعة لهذا المدرب حتى الآن.</div>
            @else
                <div class="trainer-files-grid">
                    @foreach($trainerFiles as $trainerFile)
                        <article class="trainer-file-card">
                            <a href="{{ $trainerFile->imageUrl() }}" target="_blank" rel="noopener noreferrer" class="trainer-file-image-link">
                                <img src="{{ $trainerFile->imageUrl() }}" alt="{{ $trainerFile->title }}" class="trainer-file-image">
                            </a>

                            <div class="trainer-file-body">
                                <div class="trainer-file-title">{{ $trainerFile->title }}</div>
                                <div class="table-action-group trainer-file-actions">
                                    <a href="{{ $trainerFile->imageUrl() }}" target="_blank" rel="noopener noreferrer" class="btn action-btn">عرض</a>
                                    <a href="{{ route('trainers.files.edit', [$trainer, $trainerFile]) }}" class="btn action-btn">تعديل</a>
                                    <form action="{{ route('trainers.files.destroy', [$trainer, $trainerFile]) }}" method="POST" class="inline-form">
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
    </div>
@endsection
