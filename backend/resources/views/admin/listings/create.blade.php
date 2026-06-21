@extends('admin.layouts.app')

@section('title', 'إضافة خدمة')
@section('subtitle', 'إضافة خدمة أو منشأة داخل أحد الأقسام')

@section('actions')
    <a class="button secondary" href="{{ route('admin.listings.index') }}">رجوع</a>
@endsection

@section('content')
    <div class="panel">
        <form method="post" action="{{ route('admin.listings.store') }}" enctype="multipart/form-data">
            @csrf
            @include('admin.listings.form')
            <div class="actions" style="margin-top: 16px">
                <button class="button" type="submit">حفظ الخدمة</button>
                <a class="button secondary" href="{{ route('admin.listings.index') }}">إلغاء</a>
            </div>
        </form>
    </div>
@endsection
