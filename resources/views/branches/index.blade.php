@extends('layouts.dashboard')

@section('content')
    <div class="stacked-content">
        <section class="panel-card">
            <h2 class="section-title">إضافة فرع</h2>
            <form action="{{ route('branches.store') }}" method="POST" class="form-grid top-form-grid branch-inline-form">
                @csrf
                <div>
                    <label class="form-label" for="branch_name">اسم الفرع</label>
                    <input type="text" id="branch_name" name="name" class="form-control" value="{{ old('name') }}">
                </div>
                <div class="form-submit-row">
                    <button type="submit" class="btn primary-btn">إضافة</button>
                </div>
            </form>
        </section>

        <section class="panel-card table-card wide-card">
            <h2 class="section-title">جدول الفروع</h2>
            @if($branches->isEmpty())
                <div class="empty-state">لا توجد فروع</div>
            @else
                <div class="table-responsive">
                    <table class="table align-middle app-table">
                        <thead>
                            <tr>
                                <th>الفرع</th>
                                <th>المستخدمون</th>
                                <th class="table-actions">الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($branches as $branch)
                                <tr>
                                    <td>{{ $branch->name }}</td>
                                    <td>{{ $branch->users_count }}</td>
                                    <td class="table-actions">
                                        <a href="{{ route('branches.edit', $branch) }}" class="btn action-btn">تعديل</a>
                                        <form action="{{ route('branches.destroy', $branch) }}" method="POST" class="inline-form">
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
