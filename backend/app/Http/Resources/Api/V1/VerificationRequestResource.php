<?php

namespace App\Http\Resources\Api\V1;

use App\Support\PublicStorageUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VerificationRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'user' => new UserResource($this->whenLoaded('user')),
            'category' => new CategoryResource($this->whenLoaded('category')),
            'business_name' => $this->business_name,
            'business_type' => $this->business_type,
            'commercial_registration_number' => $this->commercial_registration_number,
            'license_number' => $this->license_number,
            'national_id_image' => $this->national_id_image,
            'national_id_image_url' => $this->fileUrl($this->national_id_image),
            'commercial_registration_image' => $this->commercial_registration_image,
            'commercial_registration_image_url' => $this->fileUrl($this->commercial_registration_image),
            'business_license_image' => $this->business_license_image,
            'business_license_image_url' => $this->fileUrl($this->business_license_image),
            'ownership_or_rent_contract_image' => $this->ownership_or_rent_contract_image,
            'ownership_or_rent_contract_image_url' => $this->fileUrl($this->ownership_or_rent_contract_image),
            'business_location_latitude' => $this->business_location_latitude,
            'business_location_longitude' => $this->business_location_longitude,
            'notes' => $this->notes,
            'status' => $this->status,
            'admin_notes' => $this->admin_notes,
            'reviewed_at' => $this->reviewed_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }

    private function fileUrl(?string $path): ?string
    {
        return PublicStorageUrl::fromPath($path);
    }
}
