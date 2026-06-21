<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\BookingRequest;
use App\Models\Category;
use App\Models\City;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class AdminDashboardController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'data' => [
                'stats' => [
                    'users' => User::query()->count(),
                    'listings' => Listing::query()->count(),
                    'bookings' => BookingRequest::query()->whereIn('status', ['accepted', 'confirmed'])->count(),
                    'booking_requests' => BookingRequest::query()->count(),
                    'active_listings' => Listing::query()->where('status', 'active')->count(),
                    'pending_listings' => Listing::query()->where('status', 'pending')->count(),
                    'featured_listings' => Listing::query()->where('is_featured', true)->count(),
                    'categories' => Category::query()->count(),
                    'cities' => City::query()->count(),
                ],
                'recent' => [
                    'listings' => Listing::query()
                        ->with('category')
                        ->latest('id')
                        ->take(5)
                        ->get(['id', 'category_id', 'title_ar', 'status', 'is_featured', 'created_at']),
                    'booking_requests' => BookingRequest::query()
                        ->with('listing:id,title_ar')
                        ->latest('id')
                        ->take(5)
                        ->get(),
                ],
            ],
        ]);
    }
}
