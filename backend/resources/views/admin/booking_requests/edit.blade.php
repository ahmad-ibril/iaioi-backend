@extends('admin.layouts.app')

@section('title', 'إدارة طلب')
@section('subtitle', 'طلب رقم #' . $bookingRequest->id)

@section('actions')
    <a class="button secondary" href="{{ route('admin.booking-requests.index') }}">رجوع</a>
@endsection

@section('content')
    <div class="grid cols-2">
        <div class="panel">
            <h2 style="font-size: 18px; margin: 0 0 12px">تفاصيل الطلب</h2>
            <table>
                <tr>
                    <th>الخدمة</th>
                    <td>{{ $bookingRequest->listing?->title_ar }}</td>
                </tr>
                <tr>
                    <th>القسم</th>
                    <td>{{ $bookingRequest->listing?->category?->name_ar }}</td>
                </tr>
                <tr>
                    <th>العميل</th>
                    <td>{{ $bookingRequest->contact_name ?: $bookingRequest->user?->name }}</td>
                </tr>
                <tr>
                    <th>الهاتف</th>
                    <td>{{ $bookingRequest->contact_phone ?: $bookingRequest->user?->phone }}</td>
                </tr>
                <tr>
                    <th>الفترة</th>
                    <td>
                        {{ optional($bookingRequest->date_from)->format('Y-m-d') ?: 'غير محدد' }}
                        @if ($bookingRequest->date_to)
                            - {{ $bookingRequest->date_to->format('Y-m-d') }}
                        @endif
                    </td>
                </tr>
                <tr>
                    <th>الكمية</th>
                    <td>{{ $bookingRequest->quantity }}</td>
                </tr>
                <tr>
                    <th>ملاحظات العميل</th>
                    <td>{{ $bookingRequest->notes ?: 'لا يوجد' }}</td>
                </tr>
            </table>
        </div>

        <div class="panel">
            <h2 style="font-size: 18px; margin: 0 0 12px">تحديث الحالة</h2>
            <form method="post" action="{{ route('admin.booking-requests.update', $bookingRequest) }}">
                @csrf
                @method('PUT')

                <div class="field">
                    <label for="status">الحالة</label>
                    <select id="status" name="status" required>
                        @foreach ($statuses as $value => $label)
                            <option value="{{ $value }}" @selected(old('status', $bookingRequest->status) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="field">
                    <label for="admin_notes">ملاحظات الأدمن</label>
                    <textarea id="admin_notes" name="admin_notes">{{ old('admin_notes', $bookingRequest->admin_notes) }}</textarea>
                </div>

                <div class="actions">
                    <button class="button" type="submit">حفظ الحالة</button>
                    <a class="button secondary" href="{{ route('admin.booking-requests.index') }}">إلغاء</a>
                </div>
            </form>
        </div>
    </div>
@endsection
