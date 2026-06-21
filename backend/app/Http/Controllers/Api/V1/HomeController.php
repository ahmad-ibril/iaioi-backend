<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\CategoryResource;
use App\Http\Resources\Api\V1\ListingResource;
use App\Models\AppBanner;
use App\Models\Category;
use App\Models\Listing;
use App\Support\PublicStorageUrl;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class HomeController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $categories = Category::query()
            ->active()
            ->with(['filters' => fn ($query) => $query->filterable(), 'children.filters'])
            ->withCount(['listings' => fn ($query) => $query->active()])
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $latestListings = Listing::query()
            ->active()
            ->with(['category', 'country', 'city', 'images', 'media'])
            ->latest('published_at')
            ->latest('id')
            ->limit(10)
            ->get();

        $banners = Schema::hasTable('app_banners')
            ? AppBanner::query()
                ->where('is_active', true)
                ->where('placement', 'home')
                ->where(fn ($query) => $query
                    ->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', now()))
                ->where(fn ($query) => $query
                    ->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', now()))
                ->orderBy('sort_order')
                ->get()
                ->map(fn (AppBanner $banner): array => [
                    'id' => $banner->id,
                    'title_ar' => $banner->title_ar,
                    'title_en' => $banner->title_en,
                    'subtitle_ar' => $banner->subtitle_ar,
                    'subtitle_en' => $banner->subtitle_en,
                    'image_url' => PublicStorageUrl::fromPath($banner->image_url),
                    'link_url' => $banner->link_url,
                ])
                ->values()
            : collect();

        return response()->json([
            'data' => [
                'categories' => CategoryResource::collection($categories)->resolve($request),
                'latest_listings' => ListingResource::collection($latestListings)->resolve($request),
                'banners' => $banners,
            ],
        ]);
    }
}
