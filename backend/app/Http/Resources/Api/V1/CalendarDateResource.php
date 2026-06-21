<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CalendarDateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'date' => $this->date?->format('Y-m-d'),
            'status' => $this->status,
            'price_override' => $this->price_override,
            'available_quantity' => $this->when(isset($this->available_quantity), $this->available_quantity),
            'note' => $this->when(isset($this->note), $this->note),
        ];
    }
}
