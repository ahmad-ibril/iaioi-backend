<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\CategoryFilter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CategoryFilterController extends Controller
{
    public function index(Category $category): View
    {
        return view('admin.filters.index', [
            'category' => $category->loadCount('filters'),
            'filters' => $category->filters()->orderBy('sort_order')->paginate(20),
            'filter' => new CategoryFilter([
                'input_type' => 'text',
                'is_filterable' => true,
            ]),
            'inputTypes' => $this->inputTypes(),
        ]);
    }

    public function store(Request $request, Category $category): RedirectResponse
    {
        $data = $this->validatedData($request, $category);
        $data['options'] = $this->decodeOptions($request);
        $data['is_required'] = $request->boolean('is_required');
        $data['is_filterable'] = $request->boolean('is_filterable');
        $data['is_sortable'] = $request->boolean('is_sortable');
        unset($data['options_json']);

        $category->filters()->create($data);

        return redirect()
            ->route('admin.categories.filters.index', $category)
            ->with('success', 'تمت إضافة الفلتر بنجاح.');
    }

    public function edit(Category $category, CategoryFilter $filter): View
    {
        abort_unless($filter->category_id === $category->id, 404);

        return view('admin.filters.edit', [
            'category' => $category,
            'filter' => $filter,
            'inputTypes' => $this->inputTypes(),
        ]);
    }

    public function update(Request $request, Category $category, CategoryFilter $filter): RedirectResponse
    {
        abort_unless($filter->category_id === $category->id, 404);

        $data = $this->validatedData($request, $category, $filter);
        $data['options'] = $this->decodeOptions($request);
        $data['is_required'] = $request->boolean('is_required');
        $data['is_filterable'] = $request->boolean('is_filterable');
        $data['is_sortable'] = $request->boolean('is_sortable');
        unset($data['options_json']);

        $filter->update($data);

        return redirect()
            ->route('admin.categories.filters.index', $category)
            ->with('success', 'تم تعديل الفلتر بنجاح.');
    }

    public function destroy(Category $category, CategoryFilter $filter): RedirectResponse
    {
        abort_unless($filter->category_id === $category->id, 404);

        $filter->delete();

        return redirect()
            ->route('admin.categories.filters.index', $category)
            ->with('success', 'تم حذف الفلتر بنجاح.');
    }

    private function validatedData(Request $request, Category $category, ?CategoryFilter $filter = null): array
    {
        return $request->validate([
            'key' => [
                'required',
                'string',
                'max:80',
                Rule::unique('category_filters', 'key')
                    ->where('category_id', $category->id)
                    ->ignore($filter?->id),
            ],
            'label_ar' => ['required', 'string', 'max:255'],
            'label_en' => ['nullable', 'string', 'max:255'],
            'input_type' => ['required', Rule::in(array_keys($this->inputTypes()))],
            'options_json' => ['nullable', 'json'],
            'unit_ar' => ['nullable', 'string', 'max:50'],
            'unit_en' => ['nullable', 'string', 'max:50'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);
    }

    private function decodeOptions(Request $request): ?array
    {
        if (! $request->filled('options_json')) {
            return null;
        }

        return json_decode($request->input('options_json'), true);
    }

    private function inputTypes(): array
    {
        return [
            'text' => 'نص',
            'number' => 'رقم',
            'boolean' => 'نعم / لا',
            'select' => 'اختيار واحد',
            'multi_select' => 'اختيارات متعددة',
            'date' => 'تاريخ',
            'time' => 'وقت',
            'rating' => 'تقييم',
        ];
    }
}
