@extends('admin.layouts.app')

@section('title', 'تعديل فلتر')
@section('subtitle', $category->name_ar . ' - ' . $filter->label_ar)

@section('actions')
    <a class="button secondary" href="{{ route('admin.categories.filters.index', $category) }}">رجوع للفلاتر</a>
@endsection

@section('content')
    <div class="panel">
        <form method="post" action="{{ route('admin.categories.filters.update', [$category, $filter]) }}">
            @csrf
            @method('PUT')
            @include('admin.filters.form')
            <div class="actions" style="margin-top: 16px">
                <button class="button" type="submit">حفظ التعديلات</button>
                <a class="button secondary" href="{{ route('admin.categories.filters.index', $category) }}">إلغاء</a>
            </div>
        </form>
    </div>
@endsection
