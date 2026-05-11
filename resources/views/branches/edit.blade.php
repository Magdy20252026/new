@extends('layouts.dashboard')

@section('content')
    <section class="panel-card form-panel narrow-panel">
        <h2 class="section-title">تعديل الفرع</h2>
        <form action="{{ route('branches.update', $branch) }}" method="POST" class="form-grid">
            @csrf
            @method('PUT')
            <div>
                <label class="form-label" for="branch_name">اسم الفرع</label>
                <input type="text" id="branch_name" name="name" class="form-control" value="{{ old('name', $branch->name) }}">
            </div>
            <div class="form-actions-row">
                <button type="submit" class="btn primary-btn">حفظ</button>
                <a href="{{ route('branches.index') }}" class="btn action-btn">رجوع</a>
            </div>
        </form>
    </section>
@endsection
