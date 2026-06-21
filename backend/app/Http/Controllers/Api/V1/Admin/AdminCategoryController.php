<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\CategoryResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdminCategoryController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $categories = Category::query()
            ->with(['filters', 'children'])
            ->withCount('listings')
            ->when($request->filled('q'), function ($query) use ($request): void {
                $term = $request->string('q')->toString();

                $query->where(function ($query) use ($term): void {
                    $query
                        ->where('name_ar', 'like', "%{$term}%")
                        ->orWhere('name_en', 'like', "%{$term}%")
                        ->orWhere('slug', 'like', "%{$term}%");
                });
            })
            ->when($request->filled('group_key'), fn ($query) => $query->where('group_key', $request->input('group_key')))
            ->orderBy('group_key')
            ->orderBy('sort_order')
            ->paginate(min($request->integer('per_page', 50), 100));

        return CategoryResource::collection($categories);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'parent_id' => ['nullable', 'exists:categories,id'],
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:categories,slug'],
            'group_key' => ['nullable', 'string', 'max:80'],
            'description_ar' => ['nullable', 'string'],
            'description_en' => ['nullable', 'string'],
            'icon' => ['nullable', 'string', 'max:100'],
            'supports_booking' => ['nullable', 'boolean'],
            'settings' => ['nullable', 'array'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['slug'] = $data['slug'] ?? $this->makeSlug($data['name_en'] ?? null);

        $category = Category::create($data);

        return (new CategoryResource($category->load('filters')))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Category $category): CategoryResource
    {
        return new CategoryResource($category->load(['filters', 'children', 'parent'])->loadCount('listings'));
    }

    public function update(Request $request, Category $category): CategoryResource
    {
        $data = $request->validate([
            'parent_id' => ['nullable', 'exists:categories,id'],
            'name_ar' => ['sometimes', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('categories', 'slug')->ignore($category->id)],
            'group_key' => ['nullable', 'string', 'max:80'],
            'description_ar' => ['nullable', 'string'],
            'description_en' => ['nullable', 'string'],
            'icon' => ['nullable', 'string', 'max:100'],
            'supports_booking' => ['nullable', 'boolean'],
            'settings' => ['nullable', 'array'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if (($data['parent_id'] ?? null) === $category->id) {
            abort(422, 'A category cannot be its own parent.');
        }

        $category->update($data);

        return new CategoryResource($category->refresh()->load(['filters', 'children', 'parent'])->loadCount('listings'));
    }

    public function destroy(Category $category): JsonResponse
    {
        $category->delete();

        return response()->json(['message' => 'Category deleted.']);
    }

    private function makeSlug(?string $name): string
    {
        $base = Str::slug($name ?: 'category');

        return ($base ?: 'category').'-'.Str::lower(Str::random(8));
    }
}
