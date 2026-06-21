@extends('admin.layouts.app')

@section('title', 'إضافة قسم')
@section('subtitle', 'إنشاء قسم جديد يمكن استخدامه في التطبيق ولوحة التحكم')

@section('actions')
    <a class="button secondary" href="{{ route('admin.categories.index') }}">رجوع</a>
@endsection

@section('content')
    <div class="panel">
        <form method="post" action="{{ route('admin.categories.store') }}">
            @csrf
            @include('admin.categories.form')
            <div class="actions" style="margin-top: 16px">
                <button class="button" type="submit">حفظ القسم</button>
                <a class="button secondary" href="{{ route('admin.categories.index') }}">إلغاء</a>
            </div>
        </form>
    </div>
@endsection
