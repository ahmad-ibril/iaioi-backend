<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WantedRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'user' => new UserResource($this->whenLoaded('user')),
            'category' => new CategoryResource($this->whenLoaded('category')),
            'region' => new LocationResource($this->whenLoaded('region')),
            'title' => $this->title,
            'description' => $this->description,
            'budget' => $this->budget,
            'area_name' => $this->area_name,
            'needed_date' => $this->needed_date?->format('Y-m-d'),
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'phone' => $this->phone,
            'whatsapp' => $this->whatsapp,
            'status' => $this->status,
            'request_type' => $this->request_type,
            'media' => WantedRequestMediaResource::collection($this->whenLoaded('media')),
            'attributes' => WantedRequestAttributeResource::collection($this->whenLoaded('attributes')),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
