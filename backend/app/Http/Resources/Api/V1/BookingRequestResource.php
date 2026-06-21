<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $status = $this->status ?: 'pending';

        return [
            'id' => $this->id,
            'status' => $status,
            'status_label' => $this->statusLabel($status),
            'date_from' => $this->date_from?->toDateString(),
            'date_to' => $this->date_to?->toDateString(),
            'quantity' => $this->quantity,
            'availability_slot' => new AvailabilitySlotResource($this->whenLoaded('availabilitySlot')),
            'availability_slot_id' => $this->availability_slot_id,
            'customer_name' => $this->customer_name ?? $this->contact_name,
            'customer_phone' => $this->customer_phone ?? $this->contact_phone,
            'contact_name' => $this->contact_name ?? $this->customer_name,
            'contact_phone' => $this->contact_phone ?? $this->customer_phone,
            'notes' => $this->notes,
            'admin_notes' => $this->admin_notes,
            'created_at' => $this->created_at?->toISOString(),
            'listing' => new ListingResource($this->whenLoaded('listing')),
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user?->id,
                'name' => $this->user?->name,
                'phone' => $this->user?->phone,
                'whatsapp' => $this->user?->whatsapp,
                'email' => $this->user?->email,
            ]),
        ];
    }

    private function statusLabel(?string $status): string
    {
        return match ($status ?: 'pending') {
            'pending', 'in_review', 'new' => 'قيد المراجعة',
            'accepted', 'confirmed' => 'مقبول',
            'rejected' => 'مرفوض',
            'cancelled' => 'ملغي',
            default => 'قيد المراجعة',
        };
    }
}
