<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LocationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name_ar' => $this->name_ar,
            'name_en' => $this->name_en,
            'iso_code' => $this->when(isset($this->iso_code), $this->iso_code),
            'phone_code' => $this->when(isset($this->phone_code), $this->phone_code),
            'country_id' => $this->when(isset($this->country_id), $this->country_id),
            'latitude' => $this->when(isset($this->latitude), $this->latitude),
            'longitude' => $this->when(isset($this->longitude), $this->longitude),
            'is_active' => $this->is_active,
            'areas' => $this->whenLoaded('areas'),
        ];
    }
}
