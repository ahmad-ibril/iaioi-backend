<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\CategoryResource;
use App\Models\Category;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CategoryController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $categories = Category::query()
            ->active()
            ->with(['filters' => fn ($query) => $query->filterable(), 'children.filters'])
            ->withCount(['listings' => fn ($query) => $query->active()])
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return CategoryResource::collection($categories);
    }

    public function show(Category $category): CategoryResource
    {
        abort_unless($category->is_active, 404);

        return new CategoryResource($category->load([
            'filters' => fn ($query) => $query->filterable(),
            'children.filters',
        ]));
    }
}
