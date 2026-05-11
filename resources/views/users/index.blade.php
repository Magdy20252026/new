@extends('layouts.dashboard')

@section('content')
    <div class="content-grid">
        <section class="panel-card">
            <h2 class="section-title">إضافة مستخدم</h2>
            <form action="{{ route('users.store') }}" method="POST" class="form-grid user-form">
                @csrf
                <div>
                    <label class="form-label" for="name">الاسم</label>
                    <input type="text" id="name" name="name" class="form-control" value="{{ old('name') }}">
                </div>
                <div>
                    <label class="form-label" for="username">اسم المستخدم</label>
                    <input type="text" id="username" name="username" class="form-control" value="{{ old('username') }}">
                </div>
                <div>
                    <label class="form-label" for="password">كلمة السر</label>
                    <input type="password" id="password" name="password" class="form-control">
                </div>
                <div>
                    <label class="form-label" for="role">الصلاحية</label>
                    <select id="role" name="role" class="form-select">
                        @foreach($roles as $value => $label)
                            <option value="{{ $value }}" @selected(old('role') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label" for="scope">الفروع</label>
                    <select id="scope" name="scope" class="form-select branch-scope-select" data-target="#branchScopeCreate">
                        <option value="selected" @selected(old('scope', 'selected') === 'selected')>فروع محددة</option>
                        <option value="all" @selected(old('scope') === 'all')>كل الفروع</option>
                    </select>
                </div>
                <div id="branchScopeCreate" class="branch-scope-block {{ old('scope', 'selected') === 'all' ? 'd-none' : '' }}">
                    <label class="form-label" for="branch_ids">تحديد الفروع</label>
                    <select id="branch_ids" name="branch_ids[]" class="form-select" multiple size="6">
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" @selected(collect(old('branch_ids', [$currentBranch?->id]))->contains($branch->id))>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="btn primary-btn">إضافة</button>
            </form>
        </section>

        <section class="panel-card table-card wide-card">
            <h2 class="section-title">جدول المستخدمين</h2>
            @if($users->isEmpty())
                <div class="empty-state">لا يوجد مستخدمون</div>
            @else
                <div class="table-responsive">
                    <table class="table align-middle app-table">
                        <thead>
                            <tr>
                                <th>الاسم</th>
                                <th>اسم المستخدم</th>
                                <th>الصلاحية</th>
                                <th>الفروع</th>
                                <th class="table-actions">الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($users as $user)
                                <tr>
                                    <td>{{ $user->name }}</td>
                                    <td>{{ $user->username }}</td>
                                    <td><span class="pill-badge">{{ $user->roleLabel() }}</span></td>
                                    <td>
                                        @if($user->access_all_branches)
                                            <span class="pill-badge dark-pill">كل الفروع</span>
                                        @else
                                            <div class="branch-badges">
                                                @foreach($user->branches as $branch)
                                                    <span class="pill-badge muted-pill">{{ $branch->name }}</span>
                                                @endforeach
                                            </div>
                                        @endif
                                    </td>
                                    <td class="table-actions">
                                        <a href="{{ route('users.edit', $user) }}" class="btn action-btn">تعديل</a>
                                        <form action="{{ route('users.destroy', $user) }}" method="POST" class="inline-form">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn action-btn danger-btn">حذف</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>
@endsection
