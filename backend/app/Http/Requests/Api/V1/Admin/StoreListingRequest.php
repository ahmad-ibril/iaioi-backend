<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreListingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'category_id' => ['required', 'exists:categories,id'],
            'country_id' => ['nullable', 'exists:countries,id'],
            'city_id' => ['nullable', 'exists:cities,id'],
            'owner_user_id' => ['nullable', 'exists:users,id'],
            'title_ar' => ['required', 'string', 'max:255'],
            'title_en' => ['nullable', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:listings,slug'],
            'description_ar' => ['nullable', 'string'],
            'description_en' => ['nullable', 'string'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'area_name_ar' => ['nullable', 'string', 'max:255'],
            'area_name_en' => ['nullable', 'string', 'max:255'],
            'address_ar' => ['nullable', 'string', 'max:255'],
            'address_en' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'whatsapp' => ['nullable', 'string', 'max:40'],
            'base_price' => ['nullable', 'numeric', 'min:0'],
            'currency_code' => ['nullable', 'string', 'size:3'],
            'price_unit' => ['nullable', Rule::in(['hour', 'day', 'night', 'trip', 'product', 'person', 'month'])],
            'status' => ['nullable', Rule::in(['draft', 'inactive', 'active', 'pending', 'rejected', 'expired'])],
            'is_featured' => ['nullable', 'boolean'],
            'featured_until' => ['nullable', 'date'],
            'published_at' => ['nullable', 'date'],

            'images' => ['nullable', 'array'],
            'images.*.path' => ['required_with:images', 'string', 'max:255'],
            'images.*.alt_text_ar' => ['nullable', 'string', 'max:255'],
            'images.*.alt_text_en' => ['nullable', 'string', 'max:255'],
            'images.*.sort_order' => ['nullable', 'integer', 'min:0'],
            'images.*.is_cover' => ['nullable', 'boolean'],
            'uploaded_images' => ['nullable', 'array'],
            'uploaded_images.*' => ['file', 'image', 'max:5120'],

            'features' => ['nullable', 'array'],
            'features.*.name_ar' => ['required_with:features', 'string', 'max:255'],
            'features.*.name_en' => ['nullable', 'string', 'max:255'],
            'features.*.value_ar' => ['nullable', 'string', 'max:255'],
            'features.*.value_en' => ['nullable', 'string', 'max:255'],
            'features.*.sort_order' => ['nullable', 'integer', 'min:0'],

            'attributes' => ['nullable', 'array'],

            'calendar_dates' => ['nullable', 'array'],
            'calendar_dates.*.date' => ['required_with:calendar_dates', 'date'],
            'calendar_dates.*.status' => ['required_with:calendar_dates', Rule::in(['available', 'booked', 'blocked'])],
            'calendar_dates.*.price_override' => ['nullable', 'numeric', 'min:0'],
            'calendar_dates.*.note' => ['nullable', 'string', 'max:255'],

            'details' => ['nullable', 'array'],
            'details.chalet.area_size' => ['nullable', 'integer', 'min:0'],
            'details.chalet.rooms_count' => ['nullable', 'integer', 'min:0'],
            'details.chalet.bathrooms_count' => ['nullable', 'integer', 'min:0'],
            'details.chalet.max_guests' => ['nullable', 'integer', 'min:0'],
            'details.chalet.has_pool' => ['nullable', 'boolean'],
            'details.chalet.pool_is_heated' => ['nullable', 'boolean'],
            'details.sports_field.field_type' => ['required_with:details.sports_field', Rule::in(['football', 'padel', 'basketball', 'tennis', 'other'])],
            'details.sports_field.is_indoor' => ['nullable', 'boolean'],
            'details.sports_field.surface_type' => ['nullable', 'string', 'max:255'],
            'details.sports_field.capacity' => ['nullable', 'integer', 'min:0'],
            'details.wedding_hall.capacity' => ['nullable', 'integer', 'min:0'],
            'details.wedding_hall.hall_type' => ['nullable', 'string', 'max:255'],
            'details.wedding_hall.has_parking' => ['nullable', 'boolean'],
            'details.wedding_hall.has_catering' => ['nullable', 'boolean'],
            'details.wedding_supply.supply_type' => ['nullable', Rule::in(['product', 'package', 'service', 'other'])],
            'details.wedding_supply.quantity_available' => ['nullable', 'integer', 'min:0'],
            'details.wedding_supply.package_items' => ['nullable', 'array'],
            'details.car_rental.car_type' => ['required_with:details.car_rental', 'string', 'max:255'],
            'details.car_rental.brand' => ['nullable', 'string', 'max:255'],
            'details.car_rental.model' => ['nullable', 'string', 'max:255'],
            'details.car_rental.year' => ['nullable', 'integer', 'min:1950', 'max:2100'],
            'details.car_rental.seats_count' => ['nullable', 'integer', 'min:1'],
            'details.car_rental.with_driver' => ['nullable', 'boolean'],
            'details.car_rental.transmission' => ['nullable', Rule::in(['automatic', 'manual'])],
            'details.bus_rental.seats_count' => ['required_with:details.bus_rental', 'integer', 'min:1'],
            'details.bus_rental.bus_type' => ['nullable', 'string', 'max:255'],
            'details.bus_rental.with_driver' => ['nullable', 'boolean'],
            'details.bus_rental.has_ac' => ['nullable', 'boolean'],
            'details.hotel.stars' => ['nullable', 'integer', 'between:1,7'],
            'details.hotel.check_in_time' => ['nullable', 'date_format:H:i'],
            'details.hotel.check_out_time' => ['nullable', 'date_format:H:i'],
            'details.hotel.services' => ['nullable', 'array'],
            'details.tourism_program.destination_country' => ['required_with:details.tourism_program', 'string', 'max:255'],
            'details.tourism_program.destination_city' => ['nullable', 'string', 'max:255'],
            'details.tourism_program.departure_country' => ['nullable', 'string', 'max:255'],
            'details.tourism_program.departure_city' => ['nullable', 'string', 'max:255'],
            'details.tourism_program.duration_days' => ['nullable', 'integer', 'min:1'],
            'details.tourism_program.trip_date' => ['nullable', 'date'],
            'details.tourism_program.trip_type' => ['required_with:details.tourism_program', Rule::in(['domestic', 'international'])],
            'details.tourism_program.seats_available' => ['nullable', 'integer', 'min:0'],
            'details.tourism_program.included_services' => ['nullable', 'array'],
            'details.tourism_program.flight_times' => ['nullable', 'array'],
        ];
    }
}
