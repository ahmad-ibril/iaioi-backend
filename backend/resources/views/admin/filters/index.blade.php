@extends('admin.layouts.app')

@section('title', 'فلاتر القسم')
@section('subtitle', $category->name_ar)

@section('actions')
    <div class="actions">
        <a class="button secondary" href="{{ route('admin.categories.edit', $category) }}">تعديل القسم</a>
        <a class="button secondary" href="{{ route('admin.categories.index') }}">الأقسام</a>
    </div>
@endsection

@section('content')
    <div class="grid cols-2">
        <div class="panel">
            <strong>إضافة فلتر</strong>
            <form method="post" action="{{ route('admin.categories.filters.store', $category) }}" style="margin-top: 14px">
                @csrf
                @include('admin.filters.form')
                <div class="actions" style="margin-top: 16px">
                    <button class="button" type="submit">حفظ الفلتر</button>
                </div>
            </form>
        </div>

        <div class="panel">
            <div class="toolbar">
                <strong>الفلاتر الحالية</strong>
                <span class="badge">{{ $category->filters_count }} فلتر</span>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>المفتاح</th>
                        <th>الاسم</th>
                        <th>النوع</th>
                        <th>الحالة</th>
                        <th>إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($filters as $item)
                        <tr>
                            <td>
                                <strong>{{ $item->key }}</strong>
                                <div class="muted">{{ $item->sort_order }}</div>
                            </td>
                            <td>
                                {{ $item->label_ar }}
                                <div class="muted">{{ $item->label_en ?: '-' }}</div>
                            </td>
                            <td>{{ $inputTypes[$item->input_type] ?? $item->input_type }}</td>
                            <td>
                                <div class="actions">
                                    @if ($item->is_required)
                                        <span class="badge">إجباري</span>
                                    @endif
                                    @if ($item->is_filterable)
                                        <span class="badge green">يظهر في الفلترة</span>
                                    @endif
                                    @if ($item->is_sortable)
                                        <span class="badge gray">قابل للترتيب</span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <div class="actions">
                                    <a class="button secondary" href="{{ route('admin.categories.filters.edit', [$category, $item]) }}">تعديل</a>
                                    <form method="post" action="{{ route('admin.categories.filters.destroy', [$category, $item]) }}" onsubmit="return confirm('هل تريد حذف هذا الفلتر؟')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="button danger" type="submit">حذف</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">لا توجد فلاتر لهذا القسم.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            <div class="pagination">{{ $filters->links() }}</div>
        </div>
    </div>
@endsection
