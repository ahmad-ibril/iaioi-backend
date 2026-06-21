<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\CategoryResource;
use App\Http\Resources\Api\V1\ListingResource;
use App\Models\Category;
use App\Models\CategoryFilter;
use App\Models\Listing;
use App\Models\ListingMedia;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UserListingController extends Controller
{
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
    ];

    public function index(Request $request): AnonymousResourceCollection
    {
        $userId = $request->user()->id;

        $listings = Listing::query()
            ->with($this->relations())
            ->where(fn (Builder $query) => $query->where('user_id', $userId)->orWhere('owner_user_id', $userId))
            ->search($request->string('q')->toString())
            ->when($request->filled('status'), fn (Builder $query) => $query->where('status', $request->input('status')))
            ->when($request->filled('category_id'), fn (Builder $query) => $query->where('category_id', $request->integer('category_id')))
            ->latest('id')
            ->paginate(min($request->integer('per_page', 15), 50));

        return ListingResource::collection($listings);
    }

    public function allowedCategories(Request $request): AnonymousResourceCollection
    {
        $slugs = $this->allowedCategorySlugs($request->user());

        $categories = Category::query()
            ->active()
            ->with('filters')
            ->when($slugs !== ['*'], fn (Builder $query) => $query->whereIn('slug', $slugs))
            ->orderBy('sort_order')
            ->get();

        return CategoryResource::collection($categories);
    }

    private function relations(): array
    {
        return array_values(array_filter(
            self::RELATIONS,
            fn (string $relation) => $relation !== 'availabilitySlots'
                || Schema::hasTable('listing_availability_slots'),
        ));
    }

    public function store(Request $request): ListingResource
    {
        $data = $this->validatedData($request);
        $this->assertAllowedCategory($request, (int) $data['category_id']);

        $listing = DB::transaction(function () use ($request, $data): Listing {
            $user = $request->user();
            $title = $data['title_ar'] ?? $data['title'];
            $status = $this->statusForUser($user, $data['status'] ?? null);

            $listing = Listing::create([
                'user_id' => $user->id,
                'owner_user_id' => $user->id,
                'category_id' => $data['category_id'],
                'country_id' => $data['country_id'] ?? null,
                'city_id' => $data['region_id'] ?? $data['city_id'] ?? null,
                'title_ar' => $title,
                'title_en' => $data['title_en'] ?? null,
                'slug' => $this->makeSlug($data['slug'] ?? $title),
                'description_ar' => $data['description_ar'] ?? $data['description'] ?? null,
                'description_en' => $data['description_en'] ?? null,
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
                'area_name_ar' => $data['area_name'] ?? $data['area_name_ar'] ?? null,
                'area_name_en' => $data['area_name_en'] ?? null,
                'address_ar' => $data['address_ar'] ?? $data['address'] ?? null,
                'address_en' => $data['address_en'] ?? null,
                'phone' => $data['phone'] ?? $user->phone,
                'whatsapp' => $data['whatsapp'] ?? $user->whatsapp,
                'base_price' => $data['price'] ?? $data['base_price'] ?? null,
                'currency_code' => $data['currency_code'] ?? 'JOD',
                'price_unit' => $data['price_unit'] ?? 'day',
                'status' => $status,
                'listing_type' => 'offer',
                'published_at' => $status === 'active' ? now() : null,
            ]);

            $this->syncAttributes($listing, $data['attributes'] ?? []);
            $this->syncCalendarDates($listing, $data['calendar_dates'] ?? []);
            $this->syncAvailabilitySlots($listing, $data['availability_slots'] ?? []);
            $this->storeUploadedMedia($listing, $request);
            $this->storeUploadedImages($listing, $request);

            return $listing;
        });

        return new ListingResource($listing->load($this->relations()));
    }

    public function show(Request $request, Listing $listing): ListingResource
    {
        $this->authorizeOwnership($request, $listing);

        return new ListingResource($listing->load($this->relations()));
    }

    public function update(Request $request, Listing $listing): ListingResource
    {
        $this->authorizeOwnership($request, $listing);
        $data = $this->validatedData($request, true);

        DB::transaction(function () use ($request, $listing, $data): void {
            $user = $request->user();
            $payload = [];

            if (array_key_exists('category_id', $data)) {
                $this->assertAllowedCategory($request, (int) $data['category_id']);
                $payload['category_id'] = $data['category_id'];
            }

            if (array_key_exists('country_id', $data)) {
                $payload['country_id'] = $data['country_id'];
            }

            if (array_key_exists('region_id', $data) || array_key_exists('city_id', $data)) {
                $payload['city_id'] = $data['region_id'] ?? $data['city_id'] ?? null;
            }

            foreach ([
                'title_en',
                'description_en',
                'latitude',
                'longitude',
                'area_name_en',
                'address_ar',
                'address_en',
                'phone',
                'whatsapp',
                'currency_code',
                'price_unit',
            ] as $field) {
                if (array_key_exists($field, $data)) {
                    $payload[$field] = $data[$field];
                }
            }

            if (array_key_exists('title', $data) || array_key_exists('title_ar', $data)) {
                $payload['title_ar'] = $data['title_ar'] ?? $data['title'];
            }

            if (array_key_exists('description', $data) || array_key_exists('description_ar', $data)) {
                $payload['description_ar'] = $data['description_ar'] ?? $data['description'];
            }

            if (array_key_exists('area_name', $data) || array_key_exists('area_name_ar', $data)) {
                $payload['area_name_ar'] = $data['area_name_ar'] ?? $data['area_name'];
            }

            if (array_key_exists('address', $data)) {
                $payload['address_ar'] = $data['address'];
            }

            if (array_key_exists('price', $data) || array_key_exists('base_price', $data)) {
                $payload['base_price'] = $data['price'] ?? $data['base_price'];
            }

            if (array_key_exists('status', $data)) {
                $payload['status'] = $this->statusForUser($user, $data['status']);
                $payload['published_at'] = $payload['status'] === 'active' ? ($listing->published_at ?? now()) : null;
            }

            $listing->update($payload);

            if (array_key_exists('attributes', $data)) {
                $this->syncAttributes($listing->refresh(), $data['attributes'] ?? []);
            }

            if (array_key_exists('calendar_dates', $data)) {
                $this->syncCalendarDates($listing, $data['calendar_dates'] ?? []);
            }

            if (array_key_exists('availability_slots', $data)) {
                $this->syncAvailabilitySlots($listing, $data['availability_slots'] ?? []);
            }

            $this->storeUploadedMedia($listing, $request);
            $this->storeUploadedImages($listing, $request);
        });

        return new ListingResource($listing->refresh()->load($this->relations()));
    }

    public function destroy(Request $request, Listing $listing): JsonResponse
    {
        $this->authorizeOwnership($request, $listing);
        $listing->delete();

        return response()->json(['message' => 'تم حذف الإعلان بنجاح.']);
    }

    public function uploadMedia(Request $request, Listing $listing): ListingResource
    {
        $this->authorizeOwnership($request, $listing);

        $request->validate([
            'uploaded_media' => ['nullable', 'array'],
            'uploaded_media.*' => ['file', 'mimes:jpg,jpeg,png,webp,gif,mp4,mov,webm,avi', 'max:51200'],
            'uploaded_images' => ['nullable', 'array'],
            'uploaded_images.*' => ['file', 'image', 'max:5120'],
        ]);

        $this->storeUploadedMedia($listing, $request);
        $this->storeUploadedImages($listing, $request);

        return new ListingResource($listing->refresh()->load($this->relations()));
    }

    public function setMediaCover(Request $request, Listing $listing, ListingMedia $media): ListingResource
    {
        $this->authorizeOwnership($request, $listing);
        abort_unless($media->listing_id === $listing->id && $media->media_type === 'image', 404);

        DB::transaction(function () use ($listing, $media): void {
            $listing->media()->where('media_type', 'image')->update(['is_cover' => false]);
            $listing->images()->update(['is_cover' => false]);
            $media->update(['is_cover' => true]);
        });

        return new ListingResource($listing->refresh()->load($this->relations()));
    }

    public function deleteMedia(Request $request, Listing $listing, ListingMedia $media): ListingResource
    {
        $this->authorizeOwnership($request, $listing);
        abort_unless($media->listing_id === $listing->id, 404);

        if (! str_starts_with($media->path, 'http://') && ! str_starts_with($media->path, 'https://')) {
            Storage::disk('public')->delete($media->path);
        }

        $wasCover = $media->is_cover;
        $media->delete();

        if ($wasCover) {
            $nextCover = $listing->media()
                ->where('media_type', 'image')
                ->orderBy('sort_order')
                ->first();

            $nextCover?->update(['is_cover' => true]);
        }

        return new ListingResource($listing->refresh()->load($this->relations()));
    }

    private function validatedData(Request $request, bool $isUpdate = false): array
    {
        $required = $isUpdate ? 'sometimes' : 'required';

        return $request->validate([
            'category_id' => [$required, 'exists:categories,id'],
            'country_id' => ['nullable', 'exists:countries,id'],
            'city_id' => ['nullable', 'exists:cities,id'],
            'region_id' => ['nullable', 'exists:cities,id'],
            'title' => [$isUpdate ? 'nullable' : 'required_without:title_ar', 'string', 'max:255'],
            'title_ar' => [$isUpdate ? 'nullable' : 'required_without:title', 'string', 'max:255'],
            'title_en' => ['nullable', 'string', 'max:255'],
            'slug' => $isUpdate
                ? ['nullable', 'string', 'max:255']
                : ['nullable', 'string', 'max:255', Rule::unique('listings', 'slug')],
            'description' => ['nullable', 'string'],
            'description_ar' => ['nullable', 'string'],
            'description_en' => ['nullable', 'string'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'area_name' => ['nullable', 'string', 'max:255'],
            'area_name_ar' => ['nullable', 'string', 'max:255'],
            'area_name_en' => ['nullable', 'string', 'max:255'],
            'address_ar' => ['nullable', 'string', 'max:255'],
            'address_en' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'whatsapp' => ['nullable', 'string', 'max:40'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'base_price' => ['nullable', 'numeric', 'min:0'],
            'currency_code' => ['nullable', 'string', 'size:3'],
            'price_unit' => ['nullable', Rule::in(['hour', 'day', 'night', 'trip', 'product', 'person', 'month'])],
            'status' => ['nullable', Rule::in(['active', 'pending'])],
            'attributes' => ['nullable', 'array'],
            'calendar_dates' => ['nullable', 'array'],
            'calendar_dates.*.date' => ['required_with:calendar_dates', 'date'],
            'calendar_dates.*.status' => ['required_with:calendar_dates', Rule::in(['available', 'booked', 'blocked'])],
            'calendar_dates.*.price_override' => ['nullable', 'numeric', 'min:0'],
            'calendar_dates.*.note' => ['nullable', 'string', 'max:255'],
            'availability_slots' => ['nullable', 'array'],
            'availability_slots.*.date' => ['required_with:availability_slots', 'date'],
            'availability_slots.*.slot_name' => ['required_with:availability_slots', 'string', 'max:255'],
            'availability_slots.*.start_time' => ['nullable', 'date_format:H:i'],
            'availability_slots.*.end_time' => ['nullable', 'date_format:H:i'],
            'availability_slots.*.price' => ['nullable', 'numeric', 'min:0'],
            'availability_slots.*.status' => ['required_with:availability_slots', Rule::in(['available', 'reserved', 'unavailable', 'pending'])],
            'uploaded_media' => ['nullable', 'array'],
            'uploaded_media.*' => ['file', 'mimes:jpg,jpeg,png,webp,gif,mp4,mov,webm,avi', 'max:51200'],
            'uploaded_images' => ['nullable', 'array'],
            'uploaded_images.*' => ['file', 'image', 'max:5120'],
        ]);
    }

    private function statusForUser($user, ?string $requestedStatus): string
    {
        $requiresVerification = (bool) config("account_types.types.{$user->account_type}.requires_verification", false);

        if ($requiresVerification && $user->verification_status !== 'verified') {
            return 'pending';
        }

        return $requestedStatus ?: 'active';
    }

    private function authorizeOwnership(Request $request, Listing $listing): void
    {
        $userId = $request->user()->id;

        abort_unless($listing->user_id === $userId || $listing->owner_user_id === $userId, 404);
    }

    private function assertAllowedCategory(Request $request, int $categoryId): void
    {
        $category = Category::query()->find($categoryId);
        $allowed = $this->allowedCategorySlugs($request->user());

        if (! $category || ($allowed !== ['*'] && ! in_array($category->slug, $allowed, true))) {
            throw ValidationException::withMessages([
                'category_id' => ['نوع حسابك لا يسمح بإضافة إعلان داخل هذا القسم.'],
            ]);
        }
    }

    private function allowedCategorySlugs($user): array
    {
        $accountType = $user->account_type ?: config('account_types.default');
        $slugs = config("account_category_permissions.{$accountType}", ['*']);

        return is_array($slugs) && $slugs !== [] ? $slugs : ['*'];
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
                    "attributes.{$filter->key}" => ['هذا الحقل مطلوب للقسم المختار.'],
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
                array_merge(['category_filter_id' => $filter->id], $this->attributeValuePayload($filter, $value)),
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

    private function syncCalendarDates(Listing $listing, array $dates): void
    {
        $listing->calendarDates()->delete();

        if ($dates !== []) {
            $listing->calendarDates()->createMany($dates);
        }
    }

    private function syncAvailabilitySlots(Listing $listing, array $slots): void
    {
        $listing->availabilitySlots()->delete();

        if ($slots === []) {
            return;
        }

        $listing->availabilitySlots()->createMany(array_map(fn (array $slot) => [
            'date' => $slot['date'],
            'slot_name' => $slot['slot_name'],
            'start_time' => $slot['start_time'] ?? null,
            'end_time' => $slot['end_time'] ?? null,
            'price' => $slot['price'] ?? null,
            'status' => $slot['status'] ?? 'available',
        ], $slots));
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

    private function makeSlug(string $title): string
    {
        $slug = Str::slug($title);

        return ($slug ?: 'listing').'-'.Str::lower(Str::random(8));
    }
}
