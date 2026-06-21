@extends('admin.layouts.app')

@section('title', 'الأقسام')
@section('subtitle', 'إدارة أقسام التطبيق والفلاتر المرتبطة بها')

@section('actions')
    <a class="button" href="{{ route('admin.categories.create') }}">إضافة قسم</a>
@endsection

@section('content')
    <div class="panel">
        <form class="toolbar" method="get" action="{{ route('admin.categories.index') }}">
            <div class="form-grid" style="flex: 1">
                <div class="field">
                    <label for="q">بحث</label>
                    <input id="q" name="q" value="{{ request('q') }}" placeholder="اسم القسم أو slug">
                </div>
                <div class="field">
                    <label for="group_key">المجموعة</label>
                    <select id="group_key" name="group_key">
                        <option value="">كل المجموعات</option>
                        @foreach ($groups as $group)
                            <option value="{{ $group }}" @selected(request('group_key') === $group)>{{ $group }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="actions">
                <button class="button" type="submit">تصفية</button>
                <a class="button secondary" href="{{ route('admin.categories.index') }}">إلغاء</a>
            </div>
        </form>

        <table>
            <thead>
                <tr>
                    <th>القسم</th>
                    <th>المجموعة</th>
                    <th>الأب</th>
                    <th>الحجز</th>
                    <th>الفلاتر</th>
                    <th>الخدمات</th>
                    <th>الحالة</th>
                    <th>إجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($categories as $category)
                    <tr>
                        <td>
                            <strong>{{ $category->name_ar }}</strong>
                            <div class="muted">{{ $category->name_en ?: '-' }}</div>
                            <div class="muted">{{ $category->slug }}</div>
                        </td>
                        <td>{{ $category->group_key ?: '-' }}</td>
                        <td>{{ $category->parent?->name_ar ?: '-' }}</td>
                        <td>
                            <span @class(['badge', 'green' => $category->supports_booking, 'gray' => ! $category->supports_booking])>
                                {{ $category->supports_booking ? 'يعتمد على الحجز' : 'بدون حجز' }}
                            </span>
                        </td>
                        <td>{{ $category->filters_count }}</td>
                        <td>{{ $category->listings_count }}</td>
                        <td>
                            <span @class(['badge', 'green' => $category->is_active, 'gray' => ! $category->is_active])>
                                {{ $category->is_active ? 'مفعل' : 'متوقف' }}
                            </span>
                        </td>
                        <td>
                            <div class="actions">
                                <a class="button secondary" href="{{ route('admin.categories.edit', $category) }}">تعديل</a>
                                <a class="button secondary" href="{{ route('admin.categories.filters.index', $category) }}">الفلاتر</a>
                                <form method="post" action="{{ route('admin.categories.destroy', $category) }}" onsubmit="return confirm('هل تريد حذف هذا القسم؟')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="button danger" type="submit">حذف</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8">لا توجد أقسام مطابقة.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="pagination">{{ $categories->links() }}</div>
    </div>
@endsection
