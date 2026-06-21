<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\StoreListingRequest;
use App\Http\Requests\Api\V1\Admin\UpdateListingRequest;
use App\Http\Resources\Api\V1\ListingResource;
use App\Models\CategoryFilter;
use App\Models\Listing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AdminListingController extends Controller
{
    private const BASE_FIELDS = [
        'category_id',
        'country_id',
        'city_id',
        'owner_user_id',
        'title_ar',
        'title_en',
        'slug',
        'description_ar',
        'description_en',
        'latitude',
        'longitude',
        'area_name_ar',
        'area_name_en',
        'address_ar',
        'address_en',
        'phone',
        'whatsapp',
        'base_price',
        'currency_code',
        'price_unit',
        'status',
        'is_featured',
        'featured_until',
        'published_at',
    ];

    private const RELATIONS = [
        'category',
        'category.filters',
        'country',
        'city',
        'images',
        'media',
        'features',
        'attributes.filter',
        'calendarDates',
        'availabilitySlots',
        'chaletDetail',
        'sportsFieldDetail',
        'weddingHallDetail',
        'weddingSupplyDetail',
        'carRentalDetail',
        'busRentalDetail',
        'hotelDetail',
        'hotelRooms.images',
        'tourismProgramDetail',
    ];

    private function relations(): array
    {
        return array_values(array_filter(
            self::RELATIONS,
            fn (string $relation) => $relation !== 'availabilitySlots'
                || Schema::hasTable('listing_availability_slots'),
        ));
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Listing::query()
            ->with($this->relations())
            ->search($request->string('q')->toString())
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->input('status')))
            ->when($request->filled('category_id'), fn ($query) => $query->where('category_id', $request->integer('category_id')))
            ->latest('id');

        return ListingResource::collection($query->paginate(min($request->integer('per_page', 15), 50)));
    }

    public function store(StoreListingRequest $request): JsonResponse
    {
        $listing = DB::transaction(function () use ($request): Listing {
            $data = $request->validated();
            $baseData = array_filter(Arr::only($data, self::BASE_FIELDS), fn ($value) => $value !== null);
            $baseData['slug'] = $baseData['slug'] ?? $this->makeSlug($baseData['title_en'] ?? null);

            $listing = Listing::create($baseData);

            $this->replaceImages($listing, $data['images'] ?? []);
            $this->storeUploadedImages($listing, $request);
            $this->replaceFeatures($listing, $data['features'] ?? []);
            $this->syncAttributes($listing, $data['attributes'] ?? []);
            $this->replaceCalendarDates($listing, $data['calendar_dates'] ?? []);
            $this->syncDetails($listing, $data['details'] ?? []);

            return $listing;
        });

        return (new ListingResource($listing->load($this->relations())))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Listing $listing): ListingResource
    {
        return new ListingResource($listing->load($this->relations()));
    }

    public function update(UpdateListingRequest $request, Listing $listing): ListingResource
    {
        DB::transaction(function () use ($request, $listing): void {
            $data = $request->validated();
            $baseData = Arr::only($data, self::BASE_FIELDS);

            if (array_key_exists('slug', $baseData) && empty($baseData['slug'])) {
                unset($baseData['slug']);
            }

            $listing->update($baseData);

            if (array_key_exists('images', $data)) {
                $this->replaceImages($listing, $data['images'] ?? []);
            }

            $this->storeUploadedImages($listing, $request);

            if (array_key_exists('features', $data)) {
                $this->replaceFeatures($listing, $data['features'] ?? []);
            }

            if (array_key_exists('attributes', $data)) {
                $this->syncAttributes($listing->refresh(), $data['attributes'] ?? []);
            }

            if (array_key_exists('calendar_dates', $data)) {
                $this->replaceCalendarDates($listing, $data['calendar_dates'] ?? []);
            }

            if (array_key_exists('details', $data)) {
                $this->syncDetails($listing, $data['details'] ?? []);
            }
        });

        return new ListingResource($listing->refresh()->load($this->relations()));
    }

    public function destroy(Listing $listing): JsonResponse
    {
        $listing->delete();

        return response()->json(['message' => 'Listing deleted.']);
    }

    public function status(Request $request, Listing $listing): ListingResource
    {
        $data = $request->validate([
            'status' => ['nullable', 'in:draft,inactive,active,pending,rejected,expired'],
            'is_featured' => ['nullable', 'boolean'],
            'featured_until' => ['nullable', 'date'],
        ]);

        $listing->update([
            ...$data,
            'published_at' => ($data['status'] ?? $listing->status) === 'active'
                ? ($listing->published_at ?? now())
                : $listing->published_at,
        ]);

        return new ListingResource($listing->refresh()->load($this->relations()));
    }

    private function makeSlug(?string $title): string
    {
        $base = Str::slug($title ?: 'listing');

        return ($base ?: 'listing').'-'.Str::lower(Str::random(8));
    }

    private function replaceImages(Listing $listing, array $images): void
    {
        $listing->images()->delete();
        $listing->images()->createMany($images);
    }

    private function storeUploadedImages(Listing $listing, Request $request): void
    {
        if (! $request->hasFile('uploaded_images')) {
            return;
        }

        foreach ($request->file('uploaded_images') as $index => $file) {
            $listing->images()->create([
                'path' => $file->store("listings/{$listing->id}", 'public'),
                'sort_order' => $index,
                'is_cover' => $index === 0 && ! $listing->images()->where('is_cover', true)->exists(),
            ]);
        }
    }

    private function replaceFeatures(Listing $listing, array $features): void
    {
        $listing->features()->delete();
        $listing->features()->createMany($features);
    }

    private function replaceCalendarDates(Listing $listing, array $dates): void
    {
        $listing->calendarDates()->delete();
        $listing->calendarDates()->createMany($dates);
    }

    private function syncAttributes(Listing $listing, array $attributes): void
    {
        $filters = CategoryFilter::query()
            ->where('category_id', $listing->category_id)
            ->orderBy('sort_order')
            ->get();

        foreach ($filters as $filter) {
            if ($filter->is_required && ! array_key_exists($filter->key, $attributes)) {
                throw ValidationException::withMessages([
                    "attributes.{$filter->key}" => ['This attribute is required for the selected category.'],
                ]);
            }

            if (! array_key_exists($filter->key, $attributes)) {
                continue;
            }

            $value = $attributes[$filter->key];

            if ($value === null || $value === '') {
                $listing->attributes()->where('key', $filter->key)->delete();
                continue;
            }

            $listing->attributes()->updateOrCreate(
                ['key' => $filter->key],
                array_merge(
                    ['category_filter_id' => $filter->id],
                    $this->attributeValuePayload($filter, $value),
                ),
            );
        }
    }

    private function attributeValuePayload(CategoryFilter $filter, mixed $value): array
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
                $payload['value_json'] = is_array($value) ? array_values($value) : [$value];
                break;
            default:
                $payload['value_text'] = is_array($value)
                    ? json_encode($value, JSON_UNESCAPED_UNICODE)
                    : (string) $value;
        }

        return $payload;
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

            $payload = $details[$key] ?? [];

            if ($payload === []) {
                $listing->{$relation}()->delete();
                continue;
            }

            $listing->{$relation}()->updateOrCreate(['listing_id' => $listing->id], $payload);
        }
    }
}
