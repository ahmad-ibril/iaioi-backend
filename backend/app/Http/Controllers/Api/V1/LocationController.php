<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\LocationResource;
use App\Models\City;
use App\Models\Country;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class LocationController extends Controller
{
    public function countries(): AnonymousResourceCollection
    {
        return LocationResource::collection(
            Country::query()->active()->orderBy('name_ar')->get(),
        );
    }

    public function cities(Request $request): AnonymousResourceCollection
    {
        $cities = City::query()
            ->active()
            ->when($request->filled('country_id'), fn ($query) => $query->where('country_id', $request->integer('country_id')))
            ->with('country')
            ->orderBy('name_ar')
            ->get();

        return LocationResource::collection($cities);
    }
}
