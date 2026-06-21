<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HotelRoomResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name_ar' => $this->name_ar,
            'name_en' => $this->name_en,
            'room_type' => $this->room_type,
            'description_ar' => $this->description_ar,
            'description_en' => $this->description_en,
            'capacity_adults' => $this->capacity_adults,
            'capacity_children' => $this->capacity_children,
            'price_per_night' => $this->price_per_night,
            'currency_code' => $this->currency_code,
            'total_rooms' => $this->total_rooms,
            'is_active' => $this->is_active,
            'images' => $this->whenLoaded('images'),
            'calendar_dates' => CalendarDateResource::collection($this->whenLoaded('calendarDates')),
        ];
    }
}
