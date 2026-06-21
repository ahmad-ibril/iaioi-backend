<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\BookingRequestResource;
use App\Models\BookingRequest;
use App\Models\Listing;
use App\Models\ListingAvailabilitySlot;
use Carbon\CarbonPeriod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class BookingRequestController extends Controller
{
    private const RELATIONS = [
        'user',
        'listing.category',
        'listing.city',
        'listing.images',
        'listing.media',
        'availabilitySlot',
    ];

    public function index(Request $request): AnonymousResourceCollection
    {
        $requests = BookingRequest::query()
            ->where('user_id', $request->user()->id)
            ->with(self::RELATIONS)
            ->latest()
            ->paginate(min($request->integer('per_page', 15), 50));

        return BookingRequestResource::collection($requests);
    }

    public function ownerDashboard(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $listingQuery = Listing::query()
            ->where(fn ($query) => $query->where('user_id', $userId)->orWhere('owner_user_id', $userId));

        $bookingQuery = BookingRequest::query()
            ->whereHas('listing', fn ($query) => $query->where('user_id', $userId)->orWhere('owner_user_id', $userId));

        $listingsByStatus = (clone $listingQuery)
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $bookingsByStatus = (clone $bookingQuery)
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        return response()->json([
            'data' => [
                'listings' => [
                    'total' => (clone $listingQuery)->count(),
                    'active' => (int) ($listingsByStatus['active'] ?? 0),
                    'pending' => (int) ($listingsByStatus['pending'] ?? 0),
                    'inactive' => (int) ($listingsByStatus['inactive'] ?? 0),
                ],
                'bookings' => [
                    'total' => (clone $bookingQuery)->count(),
                    'new' => (int) (($bookingsByStatus['new'] ?? 0) + ($bookingsByStatus['pending'] ?? 0)),
                    'pending' => (int) (($bookingsByStatus['pending'] ?? 0) + ($bookingsByStatus['new'] ?? 0) + ($bookingsByStatus['in_review'] ?? 0)),
                    'in_review' => (int) (($bookingsByStatus['in_review'] ?? 0) + ($bookingsByStatus['pending'] ?? 0)),
                    'confirmed' => (int) (($bookingsByStatus['confirmed'] ?? 0) + ($bookingsByStatus['accepted'] ?? 0)),
                    'accepted' => (int) (($bookingsByStatus['accepted'] ?? 0) + ($bookingsByStatus['confirmed'] ?? 0)),
                    'rejected' => (int) ($bookingsByStatus['rejected'] ?? 0),
                    'cancelled' => (int) ($bookingsByStatus['cancelled'] ?? 0),
                ],
            ],
        ]);
    }

    public function ownerIndex(Request $request): AnonymousResourceCollection
    {
        $userId = $request->user()->id;

        $requests = BookingRequest::query()
            ->whereHas('listing', fn ($query) => $query->where('user_id', $userId)->orWhere('owner_user_id', $userId))
            ->with(self::RELATIONS)
            ->when($request->filled('status'), fn ($query) => $query->where('status', $this->normalizeStatus($request->input('status'))))
            ->latest()
            ->paginate(min($request->integer('per_page', 50), 100));

        return BookingRequestResource::collection($requests);
    }

    public function store(Request $request): BookingRequestResource
    {
        $data = $request->validate($this->bookingRules(requireListingId: true));
        $listing = Listing::query()->active()->findOrFail($data['listing_id']);

        return $this->createBookingRequest($request, $listing, $data);
    }

    public function storeForListing(Request $request, Listing $listing): BookingRequestResource
    {
        abort_unless($listing->status === 'active', 404);

        $data = $request->validate($this->bookingRules(requireListingId: false));

        return $this->createBookingRequest($request, $listing, $data);
    }

    public function show(Request $request, BookingRequest $bookingRequest): BookingRequestResource
    {
        $userId = $request->user()->id;
        $bookingRequest->loadMissing('listing');

        abort_unless(
            $bookingRequest->user_id === $userId ||
            ($bookingRequest->listing && ($bookingRequest->listing->user_id === $userId || $bookingRequest->listing->owner_user_id === $userId)),
            404,
        );

        return new BookingRequestResource($bookingRequest->load(self::RELATIONS));
    }

    public function ownerUpdate(Request $request, BookingRequest $bookingRequest): BookingRequestResource
    {
        $this->authorizeOwner($request, $bookingRequest);

        $data = $request->validate([
            'status' => ['required', Rule::in(['pending', 'accepted', 'rejected', 'cancelled', 'new', 'in_review', 'confirmed'])],
            'admin_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        return $this->transitionOwnerStatus($bookingRequest, $this->normalizeStatus($data['status']), $data['admin_notes'] ?? null);
    }

    public function accept(Request $request, BookingRequest $bookingRequest): BookingRequestResource
    {
        $this->authorizeOwner($request, $bookingRequest);

        return $this->transitionOwnerStatus($bookingRequest, 'accepted', $request->input('admin_notes'));
    }

    public function reject(Request $request, BookingRequest $bookingRequest): BookingRequestResource
    {
        $this->authorizeOwner($request, $bookingRequest);

        return $this->transitionOwnerStatus($bookingRequest, 'rejected', $request->input('admin_notes'));
    }

    public function cancel(Request $request, BookingRequest $bookingRequest): JsonResponse
    {
        abort_unless($bookingRequest->user_id === $request->user()->id, 404);

        DB::transaction(function () use ($bookingRequest): void {
            if (! in_array($bookingRequest->status, ['rejected', 'cancelled'], true)) {
                $bookingRequest->update(['status' => 'cancelled']);
                $this->releaseSlotIfPossible($bookingRequest->refresh());
            }
        });

        return response()->json(['message' => 'تم إلغاء الطلب بنجاح.']);
    }

    private function bookingRules(bool $requireListingId): array
    {
        return [
            'listing_id' => [$requireListingId ? 'required' : 'nullable', Rule::exists('listings', 'id')->where('status', 'active')],
            'availability_slot_id' => ['nullable', 'exists:listing_availability_slots,id'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:999'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:40'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:40'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    private function createBookingRequest(Request $request, Listing $listing, array $data): BookingRequestResource
    {
        $user = $request->user();

        $bookingRequest = DB::transaction(function () use ($listing, $data, $user): BookingRequest {
            $slot = null;
            if (! empty($data['availability_slot_id'])) {
                $slot = ListingAvailabilitySlot::query()
                    ->whereKey($data['availability_slot_id'])
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($slot->listing_id !== $listing->id) {
                    throw ValidationException::withMessages([
                        'availability_slot_id' => ['هذه الفترة لا تتبع الإعلان المحدد.'],
                    ]);
                }

                if ($slot->status !== 'available') {
                    throw ValidationException::withMessages([
                        'availability_slot_id' => ['هذه الفترة غير متاحة للحجز حاليا.'],
                    ]);
                }

                $slot->update(['status' => 'pending']);
            }

            $customerName = $data['customer_name'] ?? $data['contact_name'] ?? $user->name;
            $customerPhone = $data['customer_phone'] ?? $data['contact_phone'] ?? $user->phone ?? $user->whatsapp;

            return BookingRequest::create([
                'user_id' => $user->id,
                'listing_id' => $listing->id,
                'availability_slot_id' => $slot?->id,
                'status' => 'pending',
                'date_from' => $slot?->date?->toDateString() ?? $data['date_from'] ?? null,
                'date_to' => $slot?->date?->toDateString() ?? $data['date_to'] ?? null,
                'quantity' => $data['quantity'] ?? 1,
                'customer_name' => $customerName,
                'customer_phone' => $customerPhone,
                'contact_name' => $customerName,
                'contact_phone' => $customerPhone,
                'notes' => $data['notes'] ?? null,
            ]);
        });

        return new BookingRequestResource($bookingRequest->load(self::RELATIONS));
    }

    private function transitionOwnerStatus(BookingRequest $bookingRequest, string $status, ?string $adminNotes = null): BookingRequestResource
    {
        DB::transaction(function () use ($bookingRequest, $status, $adminNotes): void {
            $bookingRequest->update([
                'status' => $status,
                'admin_notes' => $adminNotes ?? $bookingRequest->admin_notes,
            ]);

            $bookingRequest->refresh();

            if ($status === 'accepted') {
                $this->reserveSlotOrDates($bookingRequest);
            }

            if (in_array($status, ['rejected', 'cancelled'], true)) {
                $this->releaseSlotIfPossible($bookingRequest);
            }

            if ($status === 'pending') {
                $bookingRequest->availabilitySlot?->update(['status' => 'pending']);
            }
        });

        return new BookingRequestResource($bookingRequest->refresh()->load(self::RELATIONS));
    }

    private function reserveSlotOrDates(BookingRequest $bookingRequest): void
    {
        $slot = $bookingRequest->availabilitySlot;
        if ($slot) {
            if (! in_array($slot->status, ['pending', 'available', 'reserved'], true)) {
                throw ValidationException::withMessages([
                    'availability_slot_id' => ['هذه الفترة لم تعد متاحة.'],
                ]);
            }

            $slot->update(['status' => 'reserved']);

            return;
        }

        $this->markBookingDatesAsBooked($bookingRequest);
    }

    private function releaseSlotIfPossible(BookingRequest $bookingRequest): void
    {
        $slot = $bookingRequest->availabilitySlot;
        if (! $slot || $slot->status === 'unavailable') {
            return;
        }

        $hasActiveRequest = BookingRequest::query()
            ->where('availability_slot_id', $slot->id)
            ->whereKeyNot($bookingRequest->id)
            ->whereIn('status', ['pending', 'accepted', 'new', 'in_review', 'confirmed'])
            ->exists();

        if (! $hasActiveRequest) {
            $slot->update(['status' => 'available']);
        }
    }

    private function normalizeStatus(string $status): string
    {
        return match ($status) {
            'new', 'in_review' => 'pending',
            'confirmed' => 'accepted',
            default => $status,
        };
    }

    private function authorizeOwner(Request $request, BookingRequest $bookingRequest): void
    {
        $userId = $request->user()->id;
        $bookingRequest->loadMissing('listing');

        abort_unless(
            $bookingRequest->listing &&
            ($bookingRequest->listing->user_id === $userId || $bookingRequest->listing->owner_user_id === $userId),
            404,
        );
    }

    private function markBookingDatesAsBooked(BookingRequest $bookingRequest): void
    {
        if (! $bookingRequest->date_from) {
            return;
        }

        $listing = $bookingRequest->listing;
        if (! $listing) {
            return;
        }

        $from = $bookingRequest->date_from->toDateString();
        $to = ($bookingRequest->date_to ?? $bookingRequest->date_from)->toDateString();

        foreach (CarbonPeriod::create($from, $to) as $date) {
            $listing->calendarDates()->updateOrCreate(
                ['date' => $date->toDateString()],
                [
                    'status' => 'booked',
                    'note' => trim('حجز مؤكد: '.($bookingRequest->customer_name ?? $bookingRequest->contact_name ?? '')),
                ],
            );
        }
    }
}
