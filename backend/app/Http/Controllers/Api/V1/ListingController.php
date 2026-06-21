<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\AvailabilitySlotResource;
use App\Http\Resources\Api\V1\CalendarDateResource;
use App\Http\Resources\Api\V1\ListingResource;
use App\Models\Category;
use App\Models\CategoryFilter;
use App\Models\Listing;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Schema;

class ListingController extends Controller
{
    private const EAGER_RELATIONS = [
        'category',
        'category.filters',
        'country',
        'city',
        'images',
        'media',
        'features',
        'attributes.filter',
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

    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Listing::query()
            ->active()
            ->with($this->eagerRelations())
            ->search($request->string('q')->toString())
            ->availableBetween($request->input('available_from'), $request->input('available_to'));

        $this->applyCommonFilters($query, $request);
        $this->applyDetailFilters($query, $request);
        $this->applyDynamicFilters($query, $request);
        $this->applySorting($query, $request);

        $perPage = min($request->integer('per_page', 15), 50);

        return ListingResource::collection($query->paginate($perPage)->withQueryString());
    }

    public function byCategory(Category $category, Request $request): AnonymousResourceCollection
    {
        $request->merge([
            'category' => $category->id,
            'category_slug' => $category->slug,
        ]);

        if ($category->slug === 'chalets' && $request->filled(['latitude', 'longitude']) && ! $request->filled('sort')) {
            $request->merge(['sort' => 'distance']);
        }

        return $this->index($request);
    }

    public function show(Listing $listing): ListingResource
    {
        abort_unless($listing->status === 'active', 404);

        $listing->increment('views_count');

        return new ListingResource($listing->load(array_merge(
            $this->eagerRelations(),
            ['calendarDates', 'hotelRooms.calendarDates'],
        )));
    }

    public function availability(Request $request, Listing $listing): AnonymousResourceCollection
    {
        abort_unless($listing->status === 'active', 404);

        if (Schema::hasTable('listing_availability_slots')) {
            $slots = $listing->availabilitySlots()
                ->when(
                    $request->filled(['from', 'to']),
                    fn ($query) => $query->whereBetween('date', [$request->input('from'), $request->input('to')]),
                )
                ->when($request->boolean('available_only'), fn ($query) => $query->where('status', 'available'))
                ->orderBy('date')
                ->orderBy('start_time')
                ->get();

            if ($slots->isNotEmpty()) {
                return AvailabilitySlotResource::collection($slots);
            }
        }

        $dates = $listing->calendarDates()
            ->when(
                $request->filled(['from', 'to']),
                fn ($query) => $query->whereBetween('date', [$request->input('from'), $request->input('to')]),
            )
            ->orderBy('date')
            ->get();

        return CalendarDateResource::collection($dates);
    }

    private function eagerRelations(): array
    {
        return array_values(array_filter(
            self::EAGER_RELATIONS,
            fn (string $relation) => $relation !== 'availabilitySlots'
                || Schema::hasTable('listing_availability_slots'),
        ));
    }

    private function applyCommonFilters(Builder $query, Request $request): void
    {
        $categorySlugs = $this->listInput($request->input('category_slugs'));
        $categoryIds = $this->listInput($request->input('category_ids'));

        if ($categorySlugs !== []) {
            $query->whereHas(
                'category',
                fn (Builder $query) => $query->whereIn('slug', $categorySlugs),
            );
        }

        if ($categoryIds !== []) {
            $query->whereHas(
                'category',
                fn (Builder $query) => $query->whereIn('id', $categoryIds),
            );
        }

        $query
            ->when($request->filled('category'), function (Builder $query) use ($request): void {
                $category = $request->input('category');

                $query->whereHas('category', function (Builder $query) use ($category): void {
                    is_numeric($category)
                        ? $query->whereKey($category)
                        : $query->where('slug', $category);
                });
            })
            ->when($request->filled('group_key'), function (Builder $query) use ($request): void {
                $query->whereHas(
                    'category',
                    fn (Builder $query) => $query->where('group_key', $request->string('group_key')->toString()),
                );
            })
            ->when($request->filled('country_id'), fn (Builder $query) => $query->where('country_id', $request->integer('country_id')))
            ->when($request->filled('city_id'), fn (Builder $query) => $query->where('city_id', $request->integer('city_id')))
            ->when($request->filled('area'), function (Builder $query) use ($request): void {
                $area = $request->string('area')->toString();

                $query->where(function (Builder $query) use ($area): void {
                    $query
                        ->where('area_name_ar', 'like', "%{$area}%")
                        ->orWhere('area_name_en', 'like', "%{$area}%");
                });
            })
            ->when($request->filled('min_price'), fn (Builder $query) => $query->where('base_price', '>=', $request->input('min_price')))
            ->when($request->filled('max_price'), fn (Builder $query) => $query->where('base_price', '<=', $request->input('max_price')));
    }

    private function listInput(mixed $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        $items = is_array($value) ? $value : explode(',', (string) $value);

        return array_values(array_filter(
            array_map(fn (mixed $item) => trim((string) $item), $items),
            fn (string $item) => $item !== '',
        ));
    }

    private function applyDetailFilters(Builder $query, Request $request): void
    {
        $query
            ->when($request->filled('min_area'), fn (Builder $query) => $query->whereHas('chaletDetail', fn (Builder $q) => $q->where('area_size', '>=', $request->integer('min_area'))))
            ->when($request->filled('rooms_count'), fn (Builder $query) => $query->whereHas('chaletDetail', fn (Builder $q) => $q->where('rooms_count', '>=', $request->integer('rooms_count'))))
            ->when($request->has('has_pool'), fn (Builder $query) => $query->whereHas('chaletDetail', fn (Builder $q) => $q->where('has_pool', $request->boolean('has_pool'))))
            ->when($request->has('pool_is_heated'), fn (Builder $query) => $query->whereHas('chaletDetail', fn (Builder $q) => $q->where('pool_is_heated', $request->boolean('pool_is_heated'))))
            ->when($request->filled('field_type'), fn (Builder $query) => $query->whereHas('sportsFieldDetail', fn (Builder $q) => $q->where('field_type', $request->input('field_type'))))
            ->when($request->filled('capacity_min'), fn (Builder $query) => $query->whereHas('weddingHallDetail', fn (Builder $q) => $q->where('capacity', '>=', $request->integer('capacity_min'))))
            ->when($request->filled('car_type'), fn (Builder $query) => $query->whereHas('carRentalDetail', fn (Builder $q) => $q->where('car_type', $request->input('car_type'))))
            ->when($request->has('with_driver'), function (Builder $query) use ($request): void {
                $withDriver = $request->boolean('with_driver');

                $query->where(function (Builder $query) use ($withDriver): void {
                    $query
                        ->whereHas('carRentalDetail', fn (Builder $q) => $q->where('with_driver', $withDriver))
                        ->orWhereHas('busRentalDetail', fn (Builder $q) => $q->where('with_driver', $withDriver));
                });
            })
            ->when($request->filled('seats_min'), function (Builder $query) use ($request): void {
                $seats = $request->integer('seats_min');

                $query->where(function (Builder $query) use ($seats): void {
                    $query
                        ->whereHas('busRentalDetail', fn (Builder $q) => $q->where('seats_count', '>=', $seats))
                        ->orWhereHas('carRentalDetail', fn (Builder $q) => $q->where('seats_count', '>=', $seats));
                });
            })
            ->when($request->filled('stars'), fn (Builder $query) => $query->whereHas('hotelDetail', fn (Builder $q) => $q->where('stars', $request->integer('stars'))))
            ->when($request->filled('trip_type'), fn (Builder $query) => $query->whereHas('tourismProgramDetail', fn (Builder $q) => $q->where('trip_type', $request->input('trip_type'))))
            ->when($request->filled('trip_date'), fn (Builder $query) => $query->whereHas('tourismProgramDetail', fn (Builder $q) => $q->whereDate('trip_date', $request->input('trip_date'))));
    }

    private function applyDynamicFilters(Builder $query, Request $request): void
    {
        $filters = $request->input('filters', []);

        if (! is_array($filters) || $filters === []) {
            return;
        }

        $category = $this->resolveCategory($request);
        $filterDefinitions = CategoryFilter::query()
            ->filterable()
            ->when($category, fn (Builder $query) => $query->where('category_id', $category->id))
            ->get()
            ->keyBy('key');

        foreach ($filters as $key => $value) {
            $definition = $filterDefinitions->get($key);

            if (! $definition || $value === null || $value === '') {
                continue;
            }

            $query->whereHas('attributes', function (Builder $query) use ($definition, $key, $value): void {
                $query->where('key', $key);

                switch ($definition->input_type) {
                    case 'number':
                    case 'rating':
                        $this->applyNumericAttributeFilter($query, $value);
                        break;
                    case 'boolean':
                        $query->where('value_boolean', filter_var($value, FILTER_VALIDATE_BOOLEAN));
                        break;
                    case 'date':
                        $query->whereDate('value_date', $value);
                        break;
                    case 'time':
                        $query->whereTime('value_time', $value);
                        break;
                    case 'multi_select':
                        $this->applyMultiSelectAttributeFilter($query, $value);
                        break;
                    default:
                        $query->where('value_text', $value);
                }
            });
        }
    }

    private function resolveCategory(Request $request): ?Category
    {
        $category = $request->input('category') ?? $request->input('category_slug');

        if (! $category) {
            return null;
        }

        return Category::query()
            ->when(
                is_numeric($category),
                fn (Builder $query) => $query->whereKey($category),
                fn (Builder $query) => $query->where('slug', $category),
            )
            ->first();
    }

    private function applyNumericAttributeFilter(Builder $query, mixed $value): void
    {
        if (is_array($value)) {
            $query
                ->when(isset($value['min']), fn (Builder $query) => $query->where('value_number', '>=', $value['min']))
                ->when(isset($value['max']), fn (Builder $query) => $query->where('value_number', '<=', $value['max']));

            return;
        }

        $query->where('value_number', $value);
    }

    private function applyMultiSelectAttributeFilter(Builder $query, mixed $value): void
    {
        $values = is_array($value) ? $value : [$value];

        $query->where(function (Builder $query) use ($values): void {
            foreach ($values as $item) {
                $query->orWhereJsonContains('value_json', $item);
            }
        });
    }

    private function applySorting(Builder $query, Request $request): void
    {
        $sort = $request->input('sort', 'newest');

        if ($sort === 'distance' && $request->filled(['latitude', 'longitude'])) {
            $query
                ->withDistance((float) $request->input('latitude'), (float) $request->input('longitude'))
                ->orderBy('distance_km');

            return;
        }

        match ($sort) {
            'price_asc' => $query->orderBy('base_price'),
            'price_desc' => $query->orderByDesc('base_price'),
            default => $query->latest('published_at')->latest('id'),
        };
    }
}
