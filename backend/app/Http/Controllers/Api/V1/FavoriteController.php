<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ListingResource;
use App\Models\Listing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class FavoriteController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $favorites = $request->user()
            ->favoriteListings()
            ->active()
            ->with(['category', 'country', 'city', 'images', 'media'])
            ->latest('favorites.created_at')
            ->paginate(min($request->integer('per_page', 15), 50));

        return ListingResource::collection($favorites);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'listing_id' => ['required', 'exists:listings,id'],
        ]);

        $request->user()->favorites()->firstOrCreate([
            'listing_id' => $data['listing_id'],
        ]);

        return response()->json(['message' => 'Listing added to favorites.'], 201);
    }

    public function destroy(Request $request, Listing $listing): JsonResponse
    {
        $request->user()
            ->favorites()
            ->where('listing_id', $listing->id)
            ->delete();

        return response()->json(['message' => 'Listing removed from favorites.']);
    }
}
