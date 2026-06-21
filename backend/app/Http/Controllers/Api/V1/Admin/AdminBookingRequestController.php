<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\BookingRequestResource;
use App\Models\BookingRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AdminBookingRequestController extends Controller
{
    private const RELATIONS = [
        'user',
        'listing.category',
        'listing.city',
        'availabilitySlot',
    ];

    public function index(Request $request): AnonymousResourceCollection
    {
        $requests = BookingRequest::query()
            ->with(self::RELATIONS)
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->input('status')))
            ->when($request->filled('listing_id'), fn ($query) => $query->where('listing_id', $request->integer('listing_id')))
            ->latest('id')
            ->paginate(min($request->integer('per_page', 50), 100));

        return BookingRequestResource::collection($requests);
    }

    public function update(Request $request, BookingRequest $bookingRequest): BookingRequestResource
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['pending', 'accepted', 'rejected', 'cancelled'])],
            'admin_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        return $this->transition($bookingRequest, $data['status'], $data['admin_notes'] ?? null);
    }

    public function accept(BookingRequest $bookingRequest): BookingRequestResource
    {
        return $this->transition($bookingRequest, 'accepted');
    }

    public function reject(BookingRequest $bookingRequest): BookingRequestResource
    {
        return $this->transition($bookingRequest, 'rejected');
    }

    public function destroy(BookingRequest $bookingRequest): JsonResponse
    {
        $bookingRequest->delete();

        return response()->json(['message' => 'Booking request deleted.']);
    }

    private function transition(BookingRequest $bookingRequest, string $status, ?string $adminNotes = null): BookingRequestResource
    {
        DB::transaction(function () use ($bookingRequest, $status, $adminNotes): void {
            $bookingRequest->loadMissing('availabilitySlot');

            if ($status === 'accepted' && $bookingRequest->availabilitySlot) {
                $slot = $bookingRequest->availabilitySlot()->lockForUpdate()->first();

                $conflict = BookingRequest::query()
                    ->where('availability_slot_id', $slot->id)
                    ->whereKeyNot($bookingRequest->id)
                    ->whereIn('status', ['accepted', 'confirmed'])
                    ->exists();

                if ($conflict || ! in_array($slot->status, ['available', 'pending', 'reserved'], true)) {
                    throw ValidationException::withMessages([
                        'availability_slot_id' => ['توجد حجز آخر على نفس اليوم والفترة.'],
                    ]);
                }

                $slot->update(['status' => 'reserved']);
            }

            if (in_array($status, ['rejected', 'cancelled'], true) && $bookingRequest->availabilitySlot) {
                $bookingRequest->availabilitySlot->update(['status' => 'available']);
            }

            $bookingRequest->update([
                'status' => $status,
                'admin_notes' => $adminNotes ?? $bookingRequest->admin_notes,
            ]);
        });

        return new BookingRequestResource($bookingRequest->refresh()->load(self::RELATIONS));
    }
}
