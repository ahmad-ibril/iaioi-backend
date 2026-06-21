@extends('admin.layouts.app')

@section('title', 'لوحة التحكم')
@section('subtitle', 'نظرة سريعة على بيانات المنصة')

@section('actions')
    <a class="button" href="{{ route('admin.categories.create') }}">إضافة قسم</a>
@endsection

@section('content')
    <div class="grid cols-3">
        <div class="stat">
            <div class="stat-value">{{ $stats['categories'] }}</div>
            <div class="muted">الأقسام</div>
        </div>
        <div class="stat">
            <div class="stat-value">{{ $stats['active_categories'] }}</div>
            <div class="muted">الأقسام المفعلة</div>
        </div>
        <div class="stat">
            <div class="stat-value">{{ $stats['filters'] }}</div>
            <div class="muted">الفلاتر الديناميكية</div>
        </div>
        <div class="stat">
            <div class="stat-value">{{ $stats['listings'] }}</div>
            <div class="muted">الخدمات والمنشآت</div>
        </div>
        <div class="stat">
            <div class="stat-value">{{ $stats['active_listings'] }}</div>
            <div class="muted">الخدمات المفعلة</div>
        </div>
        <div class="stat">
            <div class="stat-value">{{ $stats['admins'] }}</div>
            <div class="muted">مدراء النظام</div>
        </div>
    </div>

    <div style="height: 18px"></div>

    <div class="panel">
        <div class="toolbar">
            <strong>آخر الأقسام</strong>
            <a class="button secondary" href="{{ route('admin.categories.index') }}">عرض الكل</a>
        </div>
        <table>
            <thead>
                <tr>
                    <th>القسم</th>
                    <th>المجموعة</th>
                    <th>الفلاتر</th>
                    <th>الخدمات</th>
                    <th>الحالة</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($latestCategories as $category)
                    <tr>
                        <td>
                            <a href="{{ route('admin.categories.edit', $category) }}">{{ $category->name_ar }}</a>
                            <div class="muted">{{ $category->slug }}</div>
                        </td>
                        <td>{{ $category->group_key ?: '-' }}</td>
                        <td>{{ $category->filters_count }}</td>
                        <td>{{ $category->listings_count }}</td>
                        <td>
                            <span @class(['badge', 'green' => $category->is_active, 'gray' => ! $category->is_active])>
                                {{ $category->is_active ? 'مفعل' : 'متوقف' }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">لا توجد أقسام بعد.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
