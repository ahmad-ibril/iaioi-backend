<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AvailabilitySlotResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'listing_id' => $this->listing_id,
            'date' => $this->date?->format('Y-m-d'),
            'slot_name' => $this->slot_name,
            'start_time' => $this->formatTime($this->start_time),
            'end_time' => $this->formatTime($this->end_time),
            'price' => $this->price,
            'status' => $this->status,
            'status_label' => $this->statusLabel($this->status),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    private function formatTime(?string $time): ?string
    {
        if (! $time) {
            return null;
        }

        return substr($time, 0, 5);
    }

    private function statusLabel(?string $status): string
    {
        return match ($status) {
            'reserved' => 'محجوز',
            'unavailable' => 'غير متاح',
            'pending' => 'قيد المراجعة',
            default => 'متاح',
        };
    }
}
