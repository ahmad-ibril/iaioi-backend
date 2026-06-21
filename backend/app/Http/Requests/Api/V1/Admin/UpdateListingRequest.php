<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Validation\Rule;

class UpdateListingRequest extends StoreListingRequest
{
    public function rules(): array
    {
        $rules = parent::rules();

        foreach ($rules as $field => $fieldRules) {
            $rules[$field] = array_values(array_map(
                fn ($rule) => $rule === 'required' ? 'sometimes' : $rule,
                $fieldRules,
            ));
        }

        $listingId = $this->route('listing')?->id ?? $this->route('listing');
        $rules['slug'] = ['nullable', 'string', 'max:255', Rule::unique('listings', 'slug')->ignore($listingId)];

        return $rules;
    }
}
