<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\CategoryFilter;
use App\Models\City;
use App\Models\Country;
use App\Models\Listing;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ListingController extends Controller
{
    public function index(Request $request): View
    {
        $listings = Listing::query()
            ->with(['category', 'city', 'country', 'attributes.filter'])
            ->search($request->string('q')->toString())
            ->when($request->filled('category_id'), fn ($query) => $query->where('category_id', $request->integer('category_id')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->input('status')))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.listings.index', [
            'listings' => $listings,
            'categories' => $this->categoryOptions(),
            'statuses' => $this->statuses(),
        ]);
    }

    public function create(): View
    {
        return view('admin.listings.create', array_merge(
            [
                'listing' => new Listing([
                    'currency_code' => 'JOD',
                    'price_unit' => 'day',
                    'status' => 'draft',
                ]),
            ],
            $this->formData(),
            ['attributeValues' => []],
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);
        $extraData = $this->validatedExtraData($request);
        $attributes = $request->input('attributes', []);

        $listing = DB::transaction(function () use ($request, $data, $extraData, $attributes): Listing {
            $data['slug'] = $data['slug'] ?: $this->makeSlug($data['title_en'] ?: $data['title_ar']);
            $data['is_featured'] = $request->boolean('is_featured');
            $data['currency_code'] = $data['currency_code'] ?: 'JOD';

            $listing = Listing::create($data);
            $this->syncAttributes($listing, is_array($attributes) ? $attributes : []);
            $this->syncDetails($listing, $extraData['details'] ?? []);
            $this->syncHotelRooms($listing, $extraData['hotel_rooms'] ?? []);
            $this->syncFeatures($listing, $extraData['features'] ?? []);
            $this->syncCalendarDates($listing, $extraData['calendar_dates'] ?? []);
            $this->storeUploadedImages($listing, $request);
            $this->storeUploadedMedia($listing, $request);

            return $listing;
        });

        return redirect()
            ->route('admin.listings.edit', $listing)
            ->with('success', 'تم إنشاء الخدمة بنجاح.');
    }

    public function edit(Listing $listing): View
    {
        $listing->load([
            'attributes.filter',
            'category',
            'images',
            'media',
            'features',
            'calendarDates',
            'chaletDetail',
            'sportsFieldDetail',
            'weddingHallDetail',
            'weddingSupplyDetail',
            'carRentalDetail',
            'busRentalDetail',
            'hotelDetail',
            'tourismProgramDetail',
            'hotelRooms.images',
        ]);

        return view('admin.listings.edit', array_merge(
            ['listing' => $listing],
            $this->formData(),
            [
                'attributeValues' => $this->attributeValues($listing),
                'detailValues' => $this->detailValues($listing),
                'hotelRoomRows' => $this->hotelRoomRows($listing),
                'featureRows' => $this->featureRows($listing),
                'calendarRows' => $this->calendarRows($listing),
            ],
        ));
    }

    public function update(Request $request, Listing $listing): RedirectResponse
    {
        $data = $this->validatedData($request, $listing);
        $extraData = $this->validatedExtraData($request);
        $attributes = $request->input('attributes', []);

        DB::transaction(function () use ($request, $listing, $data, $extraData, $attributes): void {
            $data['slug'] = $data['slug'] ?: $listing->slug;
            $data['is_featured'] = $request->boolean('is_featured');
            $data['currency_code'] = $data['currency_code'] ?: 'JOD';

            $listing->update($data);
            $this->syncAttributes($listing->refresh(), is_array($attributes) ? $attributes : []);
            $this->syncDetails($listing, $extraData['details'] ?? []);
            if (array_key_exists('hotel_rooms', $extraData)) {
                $this->syncHotelRooms($listing, $extraData['hotel_rooms'] ?? []);
            }
            $this->syncFeatures($listing, $extraData['features'] ?? []);
            $this->syncCalendarDates($listing, $extraData['calendar_dates'] ?? []);
            $this->deleteSelectedImages($listing, $request->input('delete_image_ids', []));
            $this->deleteSelectedMedia($listing, $request->input('delete_media_ids', []));
            $this->storeUploadedImages($listing, $request);
            $this->storeUploadedMedia($listing, $request);
            $this->syncCoverImage($listing, $request->input('cover_image_id'));
            $this->syncCoverMedia($listing, $request->input('cover_media_id'));
        });

        return redirect()
            ->route('admin.listings.edit', $listing)
            ->with('success', 'تم تعديل الخدمة بنجاح.');
    }

    public function destroy(Listing $listing): RedirectResponse
    {
        $listing->delete();

        return redirect()
            ->route('admin.listings.index')
            ->with('success', 'تم حذف الخدمة بنجاح.');
    }

    private function validatedData(Request $request, ?Listing $listing = null): array
    {
        return $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'country_id' => ['nullable', 'exists:countries,id'],
            'city_id' => ['nullable', 'exists:cities,id'],
            'title_ar' => ['required', 'string', 'max:255'],
            'title_en' => ['nullable', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('listings', 'slug')->ignore($listing?->id)],
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
            'price_unit' => ['required', Rule::in(array_keys($this->priceUnits()))],
            'status' => ['required', Rule::in(array_keys($this->statuses()))],
            'published_at' => ['nullable', 'date'],
        ]);
    }

    private function validatedExtraData(Request $request): array
    {
        return $request->validate([
            'uploaded_images' => ['nullable', 'array'],
            'uploaded_images.*' => ['file', 'image', 'max:5120'],
            'delete_image_ids' => ['nullable', 'array'],
            'delete_image_ids.*' => ['integer', 'exists:listing_images,id'],
            'cover_image_id' => ['nullable', 'integer', 'exists:listing_images,id'],
            'uploaded_media' => ['nullable', 'array'],
            'uploaded_media.*' => ['file', 'mimes:jpg,jpeg,png,webp,gif,mp4,mov,webm,avi', 'max:51200'],
            'delete_media_ids' => ['nullable', 'array'],
            'delete_media_ids.*' => ['integer', 'exists:listing_media,id'],
            'cover_media_id' => ['nullable', 'integer', 'exists:listing_media,id'],

            'features' => ['nullable', 'array'],
            'features.*.name_ar' => ['nullable', 'string', 'max:255'],
            'features.*.name_en' => ['nullable', 'string', 'max:255'],
            'features.*.value_ar' => ['nullable', 'string', 'max:255'],
            'features.*.value_en' => ['nullable', 'string', 'max:255'],
            'features.*.sort_order' => ['nullable', 'integer', 'min:0'],

            'calendar_dates' => ['nullable', 'array'],
            'calendar_dates.*.date' => ['nullable', 'date'],
            'calendar_dates.*.status' => ['nullable', Rule::in(array_keys($this->calendarStatuses()))],
            'calendar_dates.*.price_override' => ['nullable', 'numeric', 'min:0'],
            'calendar_dates.*.note' => ['nullable', 'string', 'max:255'],

            'details' => ['nullable', 'array'],
            'details.chalet.area_size' => ['nullable', 'integer', 'min:0'],
            'details.chalet.rooms_count' => ['nullable', 'integer', 'min:0'],
            'details.chalet.bathrooms_count' => ['nullable', 'integer', 'min:0'],
            'details.chalet.max_guests' => ['nullable', 'integer', 'min:0'],
            'details.chalet.has_pool' => ['nullable', 'boolean'],
            'details.chalet.pool_is_heated' => ['nullable', 'boolean'],
            'details.sports_field.field_type' => ['nullable', Rule::in(['football', 'padel', 'basketball', 'tennis', 'other'])],
            'details.sports_field.is_indoor' => ['nullable', 'boolean'],
            'details.sports_field.surface_type' => ['nullable', 'string', 'max:255'],
            'details.sports_field.capacity' => ['nullable', 'integer', 'min:0'],
            'details.wedding_hall.capacity' => ['nullable', 'integer', 'min:0'],
            'details.wedding_hall.hall_type' => ['nullable', 'string', 'max:255'],
            'details.wedding_hall.has_parking' => ['nullable', 'boolean'],
            'details.wedding_hall.has_catering' => ['nullable', 'boolean'],
            'details.wedding_supply.supply_type' => ['nullable', Rule::in(['product', 'package', 'service', 'other'])],
            'details.wedding_supply.quantity_available' => ['nullable', 'integer', 'min:0'],
            'details.wedding_supply.package_items' => ['nullable'],
            'details.car_rental.car_type' => ['nullable', 'string', 'max:255'],
            'details.car_rental.brand' => ['nullable', 'string', 'max:255'],
            'details.car_rental.model' => ['nullable', 'string', 'max:255'],
            'details.car_rental.year' => ['nullable', 'integer', 'min:1950', 'max:2100'],
            'details.car_rental.seats_count' => ['nullable', 'integer', 'min:1'],
            'details.car_rental.with_driver' => ['nullable', 'boolean'],
            'details.car_rental.transmission' => ['nullable', Rule::in(['automatic', 'manual'])],
            'details.bus_rental.seats_count' => ['nullable', 'integer', 'min:1'],
            'details.bus_rental.bus_type' => ['nullable', 'string', 'max:255'],
            'details.bus_rental.with_driver' => ['nullable', 'boolean'],
            'details.bus_rental.has_ac' => ['nullable', 'boolean'],
            'details.hotel.stars' => ['nullable', 'integer', 'between:1,7'],
            'details.hotel.check_in_time' => ['nullable', 'date_format:H:i'],
            'details.hotel.check_out_time' => ['nullable', 'date_format:H:i'],
            'details.hotel.services' => ['nullable'],
            'details.tourism_program.destination_country' => ['nullable', 'string', 'max:255'],
            'details.tourism_program.destination_city' => ['nullable', 'string', 'max:255'],
            'details.tourism_program.departure_country' => ['nullable', 'string', 'max:255'],
            'details.tourism_program.departure_city' => ['nullable', 'string', 'max:255'],
            'details.tourism_program.duration_days' => ['nullable', 'integer', 'min:1'],
            'details.tourism_program.trip_date' => ['nullable', 'date'],
            'details.tourism_program.trip_type' => ['nullable', Rule::in(['domestic', 'international'])],
            'details.tourism_program.seats_available' => ['nullable', 'integer', 'min:0'],
            'details.tourism_program.included_services' => ['nullable'],
            'details.tourism_program.flight_times' => ['nullable'],

            'hotel_rooms' => ['nullable', 'array'],
            'hotel_rooms.*.name_ar' => ['nullable', 'string', 'max:255'],
            'hotel_rooms.*.name_en' => ['nullable', 'string', 'max:255'],
            'hotel_rooms.*.room_type' => ['nullable', 'string', 'max:255'],
            'hotel_rooms.*.description_ar' => ['nullable', 'string'],
            'hotel_rooms.*.capacity_adults' => ['nullable', 'integer', 'min:0'],
            'hotel_rooms.*.capacity_children' => ['nullable', 'integer', 'min:0'],
            'hotel_rooms.*.price_per_night' => ['nullable', 'numeric', 'min:0'],
            'hotel_rooms.*.currency_code' => ['nullable', 'string', 'size:3'],
            'hotel_rooms.*.total_rooms' => ['nullable', 'integer', 'min:1'],
            'hotel_rooms.*.image_url' => ['nullable', 'string', 'max:2048'],
        ]);
    }

    private function formData(): array
    {
        return [
            'categories' => $this->categoryOptions()->load('filters'),
            'countries' => Country::query()->orderBy('name_ar')->get(),
            'cities' => City::query()->with('country')->orderBy('name_ar')->get(),
            'priceUnits' => $this->priceUnits(),
            'statuses' => $this->statuses(),
            'calendarStatuses' => $this->calendarStatuses(),
            'featureRows' => [['name_ar' => '', 'name_en' => '', 'value_ar' => '', 'value_en' => '', 'sort_order' => 0]],
            'calendarRows' => [['date' => '', 'status' => 'available', 'price_override' => '', 'note' => '']],
            'detailValues' => [],
            'hotelRoomRows' => [$this->emptyHotelRoomRow()],
        ];
    }

    private function categoryOptions()
    {
        return Category::query()
            ->orderBy('group_key')
            ->orderBy('sort_order')
            ->get();
    }

    private function priceUnits(): array
    {
        return [
            'hour' => 'بالساعة',
            'day' => 'باليوم',
            'night' => 'بالليلة',
            'trip' => 'للرحلة',
            'product' => 'للمنتج',
            'person' => 'للشخص',
            'month' => 'بالشهر',
        ];
    }

    private function statuses(): array
    {
        return [
            'draft' => 'مسودة',
            'inactive' => 'غير مفعل',
            'active' => 'مفعل',
            'pending' => 'بانتظار الموافقة',
            'rejected' => 'مرفوض',
        ];
    }

    private function calendarStatuses(): array
    {
        return [
            'available' => 'متاح',
            'booked' => 'محجوز',
            'blocked' => 'مغلق',
        ];
    }

    private function storeUploadedImages(Listing $listing, Request $request): void
    {
        if (! $request->hasFile('uploaded_images')) {
            return;
        }

        $hasCover = $listing->images()->where('is_cover', true)->exists();
        $nextSortOrder = (int) $listing->images()->max('sort_order') + 1;

        foreach ($request->file('uploaded_images') as $index => $file) {
            $listing->images()->create([
                'path' => $file->store("listings/{$listing->id}", 'public'),
                'sort_order' => $nextSortOrder + $index,
                'is_cover' => ! $hasCover && $index === 0,
            ]);
        }
    }

    private function storeUploadedMedia(Listing $listing, Request $request): void
    {
        if (! $request->hasFile('uploaded_media')) {
            return;
        }

        $hasCover = $listing->media()->where('is_cover', true)->exists();
        $nextSortOrder = (int) $listing->media()->max('sort_order') + 1;

        foreach ($request->file('uploaded_media') as $index => $file) {
            $mimeType = (string) $file->getMimeType();
            $mediaType = str_starts_with($mimeType, 'video/') ? 'video' : 'image';
            $isCover = ! $hasCover && $mediaType === 'image';
            $hasCover = $hasCover || $isCover;

            $listing->media()->create([
                'media_type' => $mediaType,
                'path' => $file->store("listings/{$listing->id}/media", 'public'),
                'mime_type' => $mimeType,
                'alt_text_ar' => $listing->title_ar,
                'sort_order' => $nextSortOrder + $index,
                'is_cover' => $isCover,
            ]);
        }
    }

    private function deleteSelectedImages(Listing $listing, mixed $imageIds): void
    {
        $imageIds = array_filter((array) $imageIds);

        if ($imageIds === []) {
            return;
        }

        $images = $listing->images()->whereIn('id', $imageIds)->get();

        foreach ($images as $image) {
            Storage::disk('public')->delete($image->path);
            $image->delete();
        }
    }

    private function deleteSelectedMedia(Listing $listing, mixed $mediaIds): void
    {
        $mediaIds = array_filter((array) $mediaIds);

        if ($mediaIds === []) {
            return;
        }

        $mediaItems = $listing->media()->whereIn('id', $mediaIds)->get();

        foreach ($mediaItems as $media) {
            if (! str_starts_with($media->path, 'http://') && ! str_starts_with($media->path, 'https://')) {
                Storage::disk('public')->delete($media->path);
            }

            $media->delete();
        }
    }

    private function syncCoverImage(Listing $listing, mixed $coverImageId): void
    {
        if (! $coverImageId) {
            if (! $listing->images()->where('is_cover', true)->exists()) {
                $listing->images()->oldest('sort_order')->oldest('id')->limit(1)->update(['is_cover' => true]);
            }

            return;
        }

        $coverExists = $listing->images()->whereKey($coverImageId)->exists();

        if (! $coverExists) {
            return;
        }

        $listing->images()->update(['is_cover' => false]);
        $listing->images()->whereKey($coverImageId)->update(['is_cover' => true]);
    }

    private function syncCoverMedia(Listing $listing, mixed $coverMediaId): void
    {
        if (! $coverMediaId) {
            if (! $listing->media()->where('is_cover', true)->exists()) {
                $listing->media()->where('media_type', 'image')->oldest('sort_order')->oldest('id')->limit(1)->update(['is_cover' => true]);
            }

            return;
        }

        $coverExists = $listing->media()->whereKey($coverMediaId)->exists();

        if (! $coverExists) {
            return;
        }

        $listing->media()->update(['is_cover' => false]);
        $listing->media()->whereKey($coverMediaId)->update(['is_cover' => true]);
    }

    private function syncFeatures(Listing $listing, array $features): void
    {
        $rows = collect($features)
            ->filter(fn (array $row) => trim((string) ($row['name_ar'] ?? '')) !== '')
            ->map(fn (array $row, int $index) => [
                'name_ar' => $row['name_ar'],
                'name_en' => $row['name_en'] ?? null,
                'value_ar' => $row['value_ar'] ?? null,
                'value_en' => $row['value_en'] ?? null,
                'sort_order' => $row['sort_order'] ?? $index,
            ])
            ->values()
            ->all();

        $listing->features()->delete();

        if ($rows !== []) {
            $listing->features()->createMany($rows);
        }
    }

    private function syncCalendarDates(Listing $listing, array $dates): void
    {
        $rowsByDate = [];

        foreach ($dates as $row) {
            if (empty($row['date'])) {
                continue;
            }

            $rowsByDate[$row['date']] = [
                'date' => $row['date'],
                'status' => $row['status'] ?? 'available',
                'price_override' => $row['price_override'] ?? null,
                'note' => $row['note'] ?? null,
            ];
        }

        $listing->calendarDates()->delete();

        if ($rowsByDate !== []) {
            $listing->calendarDates()->createMany(array_values($rowsByDate));
        }
    }

    private function syncDetails(Listing $listing, array $details): void
    {
        $relations = [
            'chalet' => 'chaletDetail',
            'sports_field' => 'sportsFieldDetail',
            'wedding_hall' => 'weddingHallDetail',
            'wedding_supply' => 'weddingSupplyDetail',
            'car_rental' => 'carRentalDetail',
            'bus_rental' => 'busRentalDetail',
            'hotel' => 'hotelDetail',
            'tourism_program' => 'tourismProgramDetail',
        ];

        foreach ($relations as $key => $relation) {
            if (! array_key_exists($key, $details)) {
                continue;
            }

            $payload = $this->normalizeDetailPayload($key, (array) ($details[$key] ?? []));

            if ($this->isEmptyPayload($payload)) {
                $listing->{$relation}()->delete();
                continue;
            }

            $listing->{$relation}()->updateOrCreate(['listing_id' => $listing->id], $payload);
        }
    }

    private function syncHotelRooms(Listing $listing, array $rooms): void
    {
        $rows = collect($rooms)
            ->filter(fn (array $row) => trim((string) ($row['name_ar'] ?? '')) !== '')
            ->values();

        $listing->hotelRooms()
            ->withTrashed()
            ->get()
            ->each
            ->forceDelete();

        foreach ($rows as $row) {
            $imageUrl = trim((string) ($row['image_url'] ?? ''));
            unset($row['image_url']);

            $room = $listing->hotelRooms()->create([
                'name_ar' => $row['name_ar'],
                'name_en' => $row['name_en'] ?? null,
                'room_type' => $row['room_type'] ?? null,
                'description_ar' => $row['description_ar'] ?? null,
                'capacity_adults' => $row['capacity_adults'] ?? 2,
                'capacity_children' => $row['capacity_children'] ?? 0,
                'price_per_night' => $row['price_per_night'] ?? null,
                'currency_code' => $row['currency_code'] ?? ($listing->currency_code ?: 'JOD'),
                'total_rooms' => $row['total_rooms'] ?? 1,
                'is_active' => true,
            ]);

            if ($imageUrl !== '') {
                $room->images()->create([
                    'path' => $imageUrl,
                    'alt_text_ar' => $room->name_ar,
                    'sort_order' => 0,
                    'is_cover' => true,
                ]);
            }
        }
    }

    private function normalizeDetailPayload(string $key, array $payload): array
    {
        $arrayFields = [
            'wedding_supply' => ['package_items'],
            'hotel' => ['services'],
            'tourism_program' => ['included_services', 'flight_times'],
        ];

        foreach ($arrayFields[$key] ?? [] as $field) {
            if (! array_key_exists($field, $payload) || is_array($payload[$field])) {
                continue;
            }

            $payload[$field] = collect(preg_split('/[,،\r\n]+/u', (string) $payload[$field]))
                ->map(fn (string $item) => trim($item))
                ->filter()
                ->values()
                ->all();
        }

        $payload = collect($payload)
            ->reject(fn ($value) => $value === null || $value === '' || $value === [])
            ->all();

        $requiredFields = [
            'sports_field' => ['field_type'],
            'car_rental' => ['car_type'],
            'bus_rental' => ['seats_count'],
            'tourism_program' => ['destination_country', 'trip_type'],
        ];

        foreach ($requiredFields[$key] ?? [] as $field) {
            if (! array_key_exists($field, $payload)) {
                return [];
            }
        }

        return $payload;
    }

    private function isEmptyPayload(array $payload): bool
    {
        return count($payload) === 0;
    }

    private function syncAttributes(Listing $listing, array $attributes): void
    {
        $filters = CategoryFilter::query()
            ->where('category_id', $listing->category_id)
            ->orderBy('sort_order')
            ->get();

        $seenKeys = [];

        foreach ($filters as $filter) {
            $seenKeys[] = $filter->key;
            $value = $attributes[$filter->key] ?? null;

            if ($filter->is_required && $this->isEmptyAttributeValue($value, $filter)) {
                throw ValidationException::withMessages([
                    "attributes.{$filter->key}" => 'هذا الحقل مطلوب لهذا القسم.',
                ]);
            }

            if ($this->isEmptyAttributeValue($value, $filter)) {
                $listing->attributes()->where('key', $filter->key)->delete();
                continue;
            }

            $listing->attributes()->updateOrCreate(
                ['key' => $filter->key],
                array_merge(
                    ['category_filter_id' => $filter->id],
                    $this->attributePayload($filter, $value),
                ),
            );
        }

        $listing->attributes()
            ->whereNotIn('key', $seenKeys)
            ->delete();
    }

    private function attributePayload(CategoryFilter $filter, mixed $value): array
    {
        $payload = [
            'value_text' => null,
            'value_number' => null,
            'value_boolean' => null,
            'value_date' => null,
            'value_time' => null,
            'value_json' => null,
        ];

        switch ($filter->input_type) {
            case 'number':
            case 'rating':
                $payload['value_number'] = $value;
                break;
            case 'boolean':
                $payload['value_boolean'] = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                break;
            case 'date':
                $payload['value_date'] = $value;
                break;
            case 'time':
                $payload['value_time'] = $value;
                break;
            case 'multi_select':
                $payload['value_json'] = array_values((array) $value);
                break;
            default:
                $payload['value_text'] = is_array($value)
                    ? json_encode($value, JSON_UNESCAPED_UNICODE)
                    : (string) $value;
        }

        return $payload;
    }

    private function isEmptyAttributeValue(mixed $value, CategoryFilter $filter): bool
    {
        if ($filter->input_type === 'boolean') {
            return $value === null;
        }

        if (is_array($value)) {
            return count(array_filter($value, fn ($item) => $item !== null && $item !== '')) === 0;
        }

        return $value === null || $value === '';
    }

    private function attributeValues(Listing $listing): array
    {
        return $listing->attributes
            ->mapWithKeys(fn ($attribute) => [$attribute->key => $attribute->value])
            ->all();
    }

    private function detailValues(Listing $listing): array
    {
        $hotel = $listing->hotelDetail?->only(['stars', 'check_in_time', 'check_out_time', 'services']) ?? [];
        $hotel['check_in_time'] = isset($hotel['check_in_time']) ? substr((string) $hotel['check_in_time'], 0, 5) : null;
        $hotel['check_out_time'] = isset($hotel['check_out_time']) ? substr((string) $hotel['check_out_time'], 0, 5) : null;

        $tourismProgram = $listing->tourismProgramDetail?->only(['destination_country', 'destination_city', 'departure_country', 'departure_city', 'duration_days', 'trip_date', 'trip_type', 'seats_available', 'included_services', 'flight_times']) ?? [];
        if (($tourismProgram['trip_date'] ?? null) instanceof \Carbon\CarbonInterface) {
            $tourismProgram['trip_date'] = $tourismProgram['trip_date']->format('Y-m-d');
        }

        return [
            'chalet' => $listing->chaletDetail?->only(['area_size', 'rooms_count', 'bathrooms_count', 'max_guests', 'has_pool', 'pool_is_heated']) ?? [],
            'sports_field' => $listing->sportsFieldDetail?->only(['field_type', 'is_indoor', 'surface_type', 'capacity']) ?? [],
            'wedding_hall' => $listing->weddingHallDetail?->only(['capacity', 'hall_type', 'has_parking', 'has_catering']) ?? [],
            'wedding_supply' => $listing->weddingSupplyDetail?->only(['supply_type', 'quantity_available', 'package_items']) ?? [],
            'car_rental' => $listing->carRentalDetail?->only(['car_type', 'brand', 'model', 'year', 'seats_count', 'with_driver', 'transmission']) ?? [],
            'bus_rental' => $listing->busRentalDetail?->only(['seats_count', 'bus_type', 'with_driver', 'has_ac']) ?? [],
            'hotel' => $hotel,
            'tourism_program' => $tourismProgram,
        ];
    }

    private function hotelRoomRows(Listing $listing): array
    {
        $rows = $listing->hotelRooms
            ->map(fn ($room) => [
                'name_ar' => $room->name_ar,
                'name_en' => $room->name_en,
                'room_type' => $room->room_type,
                'description_ar' => $room->description_ar,
                'capacity_adults' => $room->capacity_adults,
                'capacity_children' => $room->capacity_children,
                'price_per_night' => $room->price_per_night,
                'currency_code' => $room->currency_code,
                'total_rooms' => $room->total_rooms,
                'image_url' => $room->images->first()?->path,
            ])
            ->values()
            ->all();

        return $rows ?: [$this->emptyHotelRoomRow()];
    }

    private function emptyHotelRoomRow(): array
    {
        return [
            'name_ar' => '',
            'name_en' => '',
            'room_type' => '',
            'description_ar' => '',
            'capacity_adults' => 2,
            'capacity_children' => 0,
            'price_per_night' => '',
            'currency_code' => 'JOD',
            'total_rooms' => 1,
            'image_url' => '',
        ];
    }

    private function featureRows(Listing $listing): array
    {
        $rows = $listing->features
            ->map(fn ($feature) => [
                'name_ar' => $feature->name_ar,
                'name_en' => $feature->name_en,
                'value_ar' => $feature->value_ar,
                'value_en' => $feature->value_en,
                'sort_order' => $feature->sort_order,
            ])
            ->values()
            ->all();

        return $rows ?: [['name_ar' => '', 'name_en' => '', 'value_ar' => '', 'value_en' => '', 'sort_order' => 0]];
    }

    private function calendarRows(Listing $listing): array
    {
        $rows = $listing->calendarDates
            ->sortBy('date')
            ->map(fn ($date) => [
                'date' => optional($date->date)->format('Y-m-d'),
                'status' => $date->status,
                'price_override' => $date->price_override,
                'note' => $date->note,
            ])
            ->values()
            ->all();

        return $rows ?: [['date' => '', 'status' => 'available', 'price_override' => '', 'note' => '']];
    }

    private function makeSlug(string $title): string
    {
        $slug = Str::slug($title);

        return ($slug ?: 'listing').'-'.Str::lower(Str::random(6));
    }
}
