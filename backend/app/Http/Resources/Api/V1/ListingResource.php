<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ListingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id ?? $this->owner_user_id,
            'category' => new CategoryResource($this->whenLoaded('category')),
            'country' => new LocationResource($this->whenLoaded('country')),
            'city' => new LocationResource($this->whenLoaded('city')),
            'title_ar' => $this->title_ar,
            'title_en' => $this->title_en,
            'slug' => $this->slug,
            'description_ar' => $this->description_ar,
            'description_en' => $this->description_en,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'distance_km' => $this->when(isset($this->distance_km), round((float) $this->distance_km, 2)),
            'area_name_ar' => $this->area_name_ar,
            'area_name_en' => $this->area_name_en,
            'address_ar' => $this->address_ar,
            'address_en' => $this->address_en,
            'phone' => $this->phone,
            'whatsapp' => $this->whatsapp,
            'base_price' => $this->base_price,
            'currency_code' => $this->currency_code,
            'price_unit' => $this->price_unit,
            'status' => $this->status,
            'listing_type' => $this->listing_type ?? 'offer',
            'is_featured' => $this->is_featured,
            'featured_until' => $this->featured_until?->toISOString(),
            'published_at' => $this->published_at?->toISOString(),
            'images' => $this->whenLoaded('images'),
            'media' => ListingMediaResource::collection($this->whenLoaded('media')),
            'features' => $this->whenLoaded('features'),
            'attributes' => ListingAttributeResource::collection($this->whenLoaded('attributes')),
            'calendar_dates' => CalendarDateResource::collection($this->whenLoaded('calendarDates')),
            'availability_slots' => AvailabilitySlotResource::collection($this->whenLoaded('availabilitySlots')),
            'details' => [
                'chalet' => $this->whenLoaded('chaletDetail'),
                'sports_field' => $this->whenLoaded('sportsFieldDetail'),
                'wedding_hall' => $this->whenLoaded('weddingHallDetail'),
                'wedding_supply' => $this->whenLoaded('weddingSupplyDetail'),
                'car_rental' => $this->whenLoaded('carRentalDetail'),
                'bus_rental' => $this->whenLoaded('busRentalDetail'),
                'hotel' => $this->whenLoaded('hotelDetail'),
                'tourism_program' => $this->whenLoaded('tourismProgramDetail'),
            ],
            'hotel_rooms' => HotelRoomResource::collection($this->whenLoaded('hotelRooms')),
        ];
    }
}
