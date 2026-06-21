<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ListingMediaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'media_type' => $this->media_type,
            'path' => $this->path,
            'url' => $this->url,
            'mime_type' => $this->mime_type,
            'alt_text_ar' => $this->alt_text_ar,
            'alt_text_en' => $this->alt_text_en,
            'sort_order' => $this->sort_order,
            'is_cover' => $this->is_cover,
        ];
    }
}
