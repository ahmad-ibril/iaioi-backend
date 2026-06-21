@extends('admin.layouts.app')

@section('title', 'طلبات الحجز والتواصل')
@section('subtitle', 'متابعة الطلبات القادمة من تطبيق المستخدمين')

@section('content')
    <div class="panel">
        <form method="get" class="toolbar">
            <div class="form-grid" style="flex: 1">
                <div class="field">
                    <label for="q">بحث</label>
                    <input id="q" name="q" value="{{ request('q') }}" placeholder="اسم العميل أو الخدمة أو رقم الهاتف">
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
            <button class="button" type="submit">تصفية</button>
        </form>

        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>الخدمة</th>
                    <th>العميل</th>
                    <th>التاريخ</th>
                    <th>الحالة</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($bookingRequests as $bookingRequest)
                    <tr>
                        <td>{{ $bookingRequest->id }}</td>
                        <td>
                            <strong>{{ $bookingRequest->listing?->title_ar }}</strong>
                            <div class="muted">{{ $bookingRequest->listing?->category?->name_ar }} - {{ $bookingRequest->listing?->city?->name_ar }}</div>
                        </td>
                        <td>
                            <strong>{{ $bookingRequest->contact_name ?: $bookingRequest->user?->name }}</strong>
                            <div class="muted">{{ $bookingRequest->contact_phone ?: $bookingRequest->user?->phone }}</div>
                        </td>
                        <td>
                            {{ optional($bookingRequest->date_from)->format('Y-m-d') ?: 'غير محدد' }}
                            @if ($bookingRequest->date_to)
                                <div class="muted">إلى {{ $bookingRequest->date_to->format('Y-m-d') }}</div>
                            @endif
                        </td>
                        <td><span class="badge">{{ $statuses[$bookingRequest->status] ?? $bookingRequest->status }}</span></td>
                        <td><a class="button secondary" href="{{ route('admin.booking-requests.edit', $bookingRequest) }}">إدارة</a></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">
                            <div class="alert">لا توجد طلبات حالياً.</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="pagination">{{ $bookingRequests->links() }}</div>
    </div>
@endsection
