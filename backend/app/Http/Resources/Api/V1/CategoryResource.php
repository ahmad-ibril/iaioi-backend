<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'parent_id' => $this->parent_id,
            'name_ar' => $this->name_ar,
            'name_en' => $this->name_en,
            'slug' => $this->slug,
            'group_key' => $this->group_key,
            'description_ar' => $this->description_ar,
            'description_en' => $this->description_en,
            'icon' => $this->icon,
            'supports_booking' => $this->supports_booking,
            'settings' => $this->settings,
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
            'listings_count' => $this->whenCounted('listings'),
            'filters' => CategoryFilterResource::collection($this->whenLoaded('filters')),
            'children' => CategoryResource::collection($this->whenLoaded('children')),
        ];
    }
}
