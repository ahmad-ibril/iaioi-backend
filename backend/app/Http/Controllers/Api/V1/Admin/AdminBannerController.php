<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppBanner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminBannerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $banners = AppBanner::query()
            ->when($request->filled('placement'), fn ($query) => $query->where('placement', $request->input('placement')))
            ->orderBy('placement')
            ->orderBy('sort_order')
            ->get();

        return response()->json(['data' => $banners]);
    }

    public function store(Request $request): JsonResponse
    {
        $banner = AppBanner::create($this->data($request, true));

        return response()->json(['data' => $banner], 201);
    }

    public function update(Request $request, AppBanner $banner): JsonResponse
    {
        $banner->update($this->data($request, false));

        return response()->json(['data' => $banner->refresh()]);
    }

    public function destroy(AppBanner $banner): JsonResponse
    {
        $banner->delete();

        return response()->json(['message' => 'Banner deleted.']);
    }

    private function data(Request $request, bool $isStore): array
    {
        return $request->validate([
            'title_ar' => [$isStore ? 'required' : 'sometimes', 'string', 'max:255'],
            'title_en' => ['nullable', 'string', 'max:255'],
            'subtitle_ar' => ['nullable', 'string', 'max:255'],
            'subtitle_en' => ['nullable', 'string', 'max:255'],
            'image_url' => ['nullable', 'string', 'max:500'],
            'link_url' => ['nullable', 'string', 'max:500'],
            'placement' => [$isStore ? 'required' : 'sometimes', Rule::in(['home', 'listings', 'details', 'special_offers'])],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }
}
