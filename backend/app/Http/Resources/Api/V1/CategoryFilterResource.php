<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryFilterResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'category_id' => $this->category_id,
            'key' => $this->key,
            'label_ar' => $this->label_ar,
            'label_en' => $this->label_en,
            'input_type' => $this->input_type,
            'options' => $this->options,
            'unit_ar' => $this->unit_ar,
            'unit_en' => $this->unit_en,
            'is_required' => $this->is_required,
            'is_filterable' => $this->is_filterable,
            'is_sortable' => $this->is_sortable,
            'sort_order' => $this->sort_order,
        ];
    }
}
