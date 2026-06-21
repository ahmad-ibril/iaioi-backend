<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\AvailabilitySlotResource;
use App\Models\Listing;
use App\Models\ListingAvailabilitySlot;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class AdminAvailabilitySlotController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $slots = ListingAvailabilitySlot::query()
            ->with('listing:id,title_ar')
            ->when($request->filled('listing_id'), fn ($query) => $query->where('listing_id', $request->integer('listing_id')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->input('status')))
            ->when($request->filled(['from', 'to']), fn ($query) => $query->whereBetween('date', [$request->input('from'), $request->input('to')]))
            ->orderBy('date')
            ->orderBy('start_time')
            ->paginate(min($request->integer('per_page', 100), 200));

        return AvailabilitySlotResource::collection($slots);
    }

    public function store(Request $request): AvailabilitySlotResource
    {
        $data = $request->validate($this->rules(requireListing: true));
        Listing::query()->findOrFail($data['listing_id']);

        $slot = ListingAvailabilitySlot::create($data);

        return new AvailabilitySlotResource($slot);
    }

    public function update(Request $request, ListingAvailabilitySlot $availabilitySlot): AvailabilitySlotResource
    {
        $data = $request->validate($this->rules(requireListing: false));
        $availabilitySlot->update($data);

        return new AvailabilitySlotResource($availabilitySlot->refresh());
    }

    public function destroy(ListingAvailabilitySlot $availabilitySlot): JsonResponse
    {
        $availabilitySlot->delete();

        return response()->json(['message' => 'Availability slot deleted.']);
    }

    private function rules(bool $requireListing): array
    {
        return [
            'listing_id' => [$requireListing ? 'required' : 'sometimes', 'exists:listings,id'],
            'date' => [$requireListing ? 'required' : 'sometimes', 'date'],
            'slot_name' => [$requireListing ? 'required' : 'sometimes', 'string', 'max:255'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'status' => [$requireListing ? 'required' : 'sometimes', Rule::in(['available', 'reserved', 'unavailable', 'pending'])],
        ];
    }
}
