<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\CategoryFilter;
use App\Models\Listing;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.dashboard', [
            'stats' => [
                'categories' => Category::count(),
                'active_categories' => Category::where('is_active', true)->count(),
                'filters' => CategoryFilter::count(),
                'listings' => Listing::count(),
                'active_listings' => Listing::where('status', 'active')->count(),
                'admins' => User::where('role', 'admin')->count(),
            ],
            'latestCategories' => Category::withCount(['filters', 'listings'])
                ->latest('id')
                ->limit(8)
                ->get(),
        ]);
    }
}
