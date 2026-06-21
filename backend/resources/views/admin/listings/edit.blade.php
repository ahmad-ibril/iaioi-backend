@extends('admin.layouts.app')

@section('title', 'تعديل خدمة')
@section('subtitle', $listing->title_ar)

@section('actions')
    <a class="button secondary" href="{{ route('admin.listings.index') }}">رجوع</a>
@endsection

@section('content')
    <div class="panel">
        <form method="post" action="{{ route('admin.listings.update', $listing) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            @include('admin.listings.form')
            <div class="actions" style="margin-top: 16px">
                <button class="button" type="submit">حفظ التعديلات</button>
                <a class="button secondary" href="{{ route('admin.listings.index') }}">إلغاء</a>
            </div>
        </form>
    </div>
@endsection
