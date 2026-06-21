<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\CategoryResource;
use App\Http\Resources\Api\V1\LocationResource;
use App\Models\Category;
use App\Models\City;
use Illuminate\Http\JsonResponse;

class FilterOptionController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'cities' => LocationResource::collection(City::query()->active()->orderBy('name_ar')->get()),
            'price_units' => ['hour', 'day', 'night', 'trip', 'product', 'person'],
            'sorts' => ['newest', 'price_asc', 'price_desc', 'distance'],
            'calendar_statuses' => ['available', 'booked', 'blocked'],
            'categories' => CategoryResource::collection(
                Category::query()
                    ->active()
                    ->with(['filters' => fn ($query) => $query->filterable()])
                    ->withCount(['listings' => fn ($query) => $query->active()])
                    ->orderBy('group_key')
                    ->orderBy('sort_order')
                    ->get(),
            ),
        ]);
    }
}
