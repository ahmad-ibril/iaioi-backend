@extends('admin.layouts.app')

@section('title', 'الخدمات والمنشآت')
@section('subtitle', 'إدارة الخدمات المعروضة داخل أقسام التطبيق')

@section('actions')
    <a class="button" href="{{ route('admin.listings.create') }}">إضافة خدمة</a>
@endsection

@section('content')
    <div class="panel">
        <form class="toolbar" method="get" action="{{ route('admin.listings.index') }}">
            <div class="form-grid" style="flex: 1">
                <div class="field">
                    <label for="q">بحث</label>
                    <input id="q" name="q" value="{{ request('q') }}" placeholder="اسم الخدمة أو المنطقة">
                </div>

                <div class="field">
                    <label for="category_id">القسم</label>
                    <select id="category_id" name="category_id">
                        <option value="">كل الأقسام</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected((string) request('category_id') === (string) $category->id)>
                                {{ $category->name_ar }} - {{ $category->slug }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="field">
                    <label for="status">الحالة</label>
                    <select id="status" name="status">
                        <option value="">كل الحالات</option>
                        @foreach ($statuses as $value => $label)
                            <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="actions">
                <button class="button" type="submit">تصفية</button>
                <a class="button secondary" href="{{ route('admin.listings.index') }}">إلغاء</a>
            </div>
        </form>

        <table>
            <thead>
                <tr>
                    <th>الخدمة</th>
                    <th>القسم</th>
                    <th>المدينة</th>
                    <th>السعر</th>
                    <th>التواصل</th>
                    <th>الحالة</th>
                    <th>إجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($listings as $listing)
                    <tr>
                        <td>
                            <strong>{{ $listing->title_ar }}</strong>
                            <div class="muted">{{ $listing->title_en ?: '-' }}</div>
                            <div class="muted">{{ $listing->slug }}</div>
                        </td>
                        <td>{{ $listing->category?->name_ar ?: '-' }}</td>
                        <td>
                            {{ $listing->city?->name_ar ?: '-' }}
                            @if ($listing->area_name_ar)
                                <div class="muted">{{ $listing->area_name_ar }}</div>
                            @endif
                        </td>
                        <td>
                            @if ($listing->base_price !== null)
                                {{ $listing->base_price }} {{ $listing->currency_code }}
                            @else
                                -
                            @endif
                        </td>
                        <td>
                            <div>{{ $listing->phone ?: '-' }}</div>
                            <div class="muted">{{ $listing->whatsapp ?: '-' }}</div>
                        </td>
                        <td>
                            <span @class(['badge', 'green' => $listing->status === 'active', 'gray' => $listing->status !== 'active'])>
                                {{ $statuses[$listing->status] ?? $listing->status }}
                            </span>
                            @if ($listing->is_featured)
                                <span class="badge">مميز</span>
                            @endif
                        </td>
                        <td>
                            <div class="actions">
                                <a class="button secondary" href="{{ route('admin.listings.edit', $listing) }}">تعديل</a>
                                <form method="post" action="{{ route('admin.listings.destroy', $listing) }}" onsubmit="return confirm('هل تريد حذف هذه الخدمة؟')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="button danger" type="submit">حذف</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">لا توجد خدمات مطابقة.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="pagination">{{ $listings->links() }}</div>
    </div>
@endsection
