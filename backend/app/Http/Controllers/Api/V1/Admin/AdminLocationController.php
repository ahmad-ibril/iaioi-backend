<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\LocationResource;
use App\Models\City;
use App\Models\CityArea;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AdminLocationController extends Controller
{
    public function cities(Request $request): AnonymousResourceCollection
    {
        $cities = City::query()
            ->with('areas')
            ->when($request->filled('country_id'), fn ($query) => $query->where('country_id', $request->integer('country_id')))
            ->orderBy('name_ar')
            ->paginate(min($request->integer('per_page', 100), 200));

        return LocationResource::collection($cities);
    }

    public function storeCity(Request $request): LocationResource
    {
        $city = City::create($this->cityData($request, true));

        return new LocationResource($city->load('areas'));
    }

    public function updateCity(Request $request, City $city): LocationResource
    {
        $city->update($this->cityData($request, false));

        return new LocationResource($city->refresh()->load('areas'));
    }

    public function destroyCity(City $city): JsonResponse
    {
        $city->delete();

        return response()->json(['message' => 'City deleted.']);
    }

    public function areas(Request $request): JsonResponse
    {
        $areas = CityArea::query()
            ->with('city:id,name_ar')
            ->when($request->filled('city_id'), fn ($query) => $query->where('city_id', $request->integer('city_id')))
            ->orderBy('city_id')
            ->orderBy('sort_order')
            ->get();

        return response()->json(['data' => $areas]);
    }

    public function storeArea(Request $request): JsonResponse
    {
        $area = CityArea::create($this->areaData($request, true));

        return response()->json(['data' => $area], 201);
    }

    public function updateArea(Request $request, CityArea $area): JsonResponse
    {
        $area->update($this->areaData($request, false));

        return response()->json(['data' => $area->refresh()]);
    }

    public function destroyArea(CityArea $area): JsonResponse
    {
        $area->delete();

        return response()->json(['message' => 'Area deleted.']);
    }

    private function cityData(Request $request, bool $isStore): array
    {
        return $request->validate([
            'country_id' => [$isStore ? 'required' : 'sometimes', 'exists:countries,id'],
            'name_ar' => [$isStore ? 'required' : 'sometimes', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }

    private function areaData(Request $request, bool $isStore): array
    {
        return $request->validate([
            'city_id' => [$isStore ? 'required' : 'sometimes', 'exists:cities,id'],
            'name_ar' => [$isStore ? 'required' : 'sometimes', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }
}
