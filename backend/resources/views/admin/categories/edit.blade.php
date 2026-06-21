@extends('admin.layouts.app')

@section('title', 'تعديل قسم')
@section('subtitle', $category->name_ar)

@section('actions')
    <div class="actions">
        <a class="button secondary" href="{{ route('admin.categories.filters.index', $category) }}">إدارة الفلاتر</a>
        <a class="button secondary" href="{{ route('admin.categories.index') }}">رجوع</a>
    </div>
@endsection

@section('content')
    <div class="grid cols-3" style="margin-bottom: 14px">
        <div class="stat">
            <div class="stat-value">{{ $category->filters_count }}</div>
            <div class="muted">الفلاتر</div>
        </div>
        <div class="stat">
            <div class="stat-value">{{ $category->listings_count }}</div>
            <div class="muted">الخدمات</div>
        </div>
        <div class="stat">
            <div class="stat-value">{{ $category->sort_order }}</div>
            <div class="muted">ترتيب العرض</div>
        </div>
    </div>

    <div class="panel">
        <form method="post" action="{{ route('admin.categories.update', $category) }}">
            @csrf
            @method('PUT')
            @include('admin.categories.form')
            <div class="actions" style="margin-top: 16px">
                <button class="button" type="submit">حفظ التعديلات</button>
                <a class="button secondary" href="{{ route('admin.categories.index') }}">إلغاء</a>
            </div>
        </form>
    </div>
@endsection
