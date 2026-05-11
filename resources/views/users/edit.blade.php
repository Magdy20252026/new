@extends('layouts.dashboard')

@section('content')
    <section class="panel-card form-panel narrow-panel">
        <h2 class="section-title">تعديل المستخدم</h2>
        <form action="{{ route('users.update', $editedUser) }}" method="POST" class="form-grid user-form">
            @csrf
            @method('PUT')
            <div>
                <label class="form-label" for="name">الاسم</label>
                <input type="text" id="name" name="name" class="form-control" value="{{ old('name', $editedUser->name) }}">
            </div>
            <div>
                <label class="form-label" for="username">اسم المستخدم</label>
                <input type="text" id="username" name="username" class="form-control" value="{{ old('username', $editedUser->username) }}">
            </div>
            <div>
                <label class="form-label" for="password">كلمة السر</label>
                <input type="password" id="password" name="password" class="form-control">
            </div>
            <div>
                <label class="form-label" for="role">الصلاحية</label>
                <select id="role" name="role" class="form-select">
                    @foreach($roles as $value => $label)
                        <option value="{{ $value }}" @selected(old('role', $editedUser->role) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label" for="scope">الفروع</label>
                <select id="scope" name="scope" class="form-select branch-scope-select" data-target="#branchScopeEdit">
                    <option value="selected" @selected(old('scope', $editedUser->access_all_branches ? 'all' : 'selected') === 'selected')>فروع محددة</option>
                    <option value="all" @selected(old('scope', $editedUser->access_all_branches ? 'all' : 'selected') === 'all')>كل الفروع</option>
                </select>
            </div>
            <div id="branchScopeEdit" class="branch-scope-block {{ old('scope', $editedUser->access_all_branches ? 'all' : 'selected') === 'all' ? 'd-none' : '' }}">
                <label class="form-label" for="branch_ids">تحديد الفروع</label>
                <select id="branch_ids" name="branch_ids[]" class="form-select" multiple size="6">
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" @selected(collect(old('branch_ids', $editedUser->branches->pluck('id')->all()))->contains($branch->id))>{{ $branch->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-actions-row">
                <button type="submit" class="btn primary-btn">حفظ</button>
                <a href="{{ route('users.index') }}" class="btn action-btn">رجوع</a>
            </div>
        </form>
    </section>
@endsection
