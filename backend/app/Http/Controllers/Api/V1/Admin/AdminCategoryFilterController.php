<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\CategoryFilterResource;
use App\Models\Category;
use App\Models\CategoryFilter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class AdminCategoryFilterController extends Controller
{
    public function index(Category $category): AnonymousResourceCollection
    {
        return CategoryFilterResource::collection(
            $category->filters()->orderBy('sort_order')->get(),
        );
    }

    public function store(Request $request, Category $category): JsonResponse
    {
        $filter = $category->filters()->create($this->validatedData($request, $category));

        return (new CategoryFilterResource($filter))
            ->response()
            ->setStatusCode(201);
    }

    public function update(Request $request, Category $category, CategoryFilter $filter): CategoryFilterResource
    {
        abort_unless($filter->category_id === $category->id, 404);

        $filter->update($this->validatedData($request, $category, $filter));

        return new CategoryFilterResource($filter->refresh());
    }

    public function destroy(Category $category, CategoryFilter $filter): JsonResponse
    {
        abort_unless($filter->category_id === $category->id, 404);

        $filter->delete();

        return response()->json(['message' => 'Filter deleted.']);
    }

    private function validatedData(Request $request, Category $category, ?CategoryFilter $filter = null): array
    {
        return $request->validate([
            'key' => [
                $filter ? 'sometimes' : 'required',
                'string',
                'max:80',
                Rule::unique('category_filters', 'key')
                    ->where('category_id', $category->id)
                    ->ignore($filter?->id),
            ],
            'label_ar' => [$filter ? 'sometimes' : 'required', 'string', 'max:255'],
            'label_en' => ['nullable', 'string', 'max:255'],
            'input_type' => ['nullable', Rule::in(['text', 'number', 'boolean', 'select', 'multi_select', 'date', 'time', 'rating'])],
            'options' => ['nullable', 'array'],
            'unit_ar' => ['nullable', 'string', 'max:50'],
            'unit_en' => ['nullable', 'string', 'max:50'],
            'is_required' => ['nullable', 'boolean'],
            'is_filterable' => ['nullable', 'boolean'],
            'is_sortable' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);
    }
}
