<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BookingRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BookingRequestController extends Controller
{
    public function index(Request $request): View
    {
        $bookingRequests = BookingRequest::query()
            ->with(['user', 'listing.category', 'listing.city'])
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->input('status')))
            ->when($request->filled('q'), function ($query) use ($request): void {
                $term = $request->string('q')->toString();

                $query->where(function ($query) use ($term): void {
                    $query
                        ->where('contact_name', 'like', "%{$term}%")
                        ->orWhere('contact_phone', 'like', "%{$term}%")
                        ->orWhereHas('user', fn ($query) => $query->where('name', 'like', "%{$term}%")->orWhere('email', 'like', "%{$term}%"))
                        ->orWhereHas('listing', fn ($query) => $query->where('title_ar', 'like', "%{$term}%"));
                });
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.booking_requests.index', [
            'bookingRequests' => $bookingRequests,
            'statuses' => $this->statuses(),
        ]);
    }

    public function edit(BookingRequest $bookingRequest): View
    {
        $bookingRequest->load(['user', 'listing.category', 'listing.city']);

        return view('admin.booking_requests.edit', [
            'bookingRequest' => $bookingRequest,
            'statuses' => $this->statuses(),
        ]);
    }

    public function update(Request $request, BookingRequest $bookingRequest): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys($this->statuses()))],
            'admin_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $bookingRequest->update($data);

        return redirect()
            ->route('admin.booking-requests.edit', $bookingRequest)
            ->with('success', 'تم تحديث طلب الحجز بنجاح.');
    }

    private function statuses(): array
    {
        return [
            'new' => 'جديد',
            'in_review' => 'قيد المراجعة',
            'confirmed' => 'مؤكد',
            'rejected' => 'مرفوض',
            'cancelled' => 'ملغي',
        ];
    }
}
