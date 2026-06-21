<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(Request $request): View
    {
        $categories = Category::query()
            ->with('parent')
            ->withCount(['filters', 'listings'])
            ->when($request->filled('q'), function ($query) use ($request): void {
                $term = $request->string('q')->toString();

                $query->where(function ($query) use ($term): void {
                    $query
                        ->where('name_ar', 'like', "%{$term}%")
                        ->orWhere('name_en', 'like', "%{$term}%")
                        ->orWhere('slug', 'like', "%{$term}%")
                        ->orWhere('group_key', 'like', "%{$term}%");
                });
            })
            ->when($request->filled('group_key'), fn ($query) => $query->where('group_key', $request->input('group_key')))
            ->orderBy('group_key')
            ->orderBy('sort_order')
            ->paginate(20)
            ->withQueryString();

        return view('admin.categories.index', [
            'categories' => $categories,
            'groups' => Category::query()
                ->whereNotNull('group_key')
                ->distinct()
                ->orderBy('group_key')
                ->pluck('group_key'),
        ]);
    }

    public function create(): View
    {
        return view('admin.categories.create', [
            'category' => new Category([
                'is_active' => true,
                'supports_booking' => true,
            ]),
            'parents' => $this->parentOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);
        $data['slug'] = $data['slug'] ?: $this->makeSlug($data['name_en'] ?? $data['name_ar']);
        $data['is_active'] = $request->boolean('is_active');
        $data['supports_booking'] = $request->boolean('supports_booking');
        $data['settings'] = $this->decodeSettings($request);
        unset($data['settings_json']);

        $category = Category::create($data);

        return redirect()
            ->route('admin.categories.edit', $category)
            ->with('success', 'تم إنشاء القسم بنجاح.');
    }

    public function edit(Category $category): View
    {
        return view('admin.categories.edit', [
            'category' => $category->loadCount(['filters', 'listings']),
            'parents' => $this->parentOptions($category),
        ]);
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $data = $this->validatedData($request, $category);
        $data['slug'] = $data['slug'] ?: $category->slug;
        $data['is_active'] = $request->boolean('is_active');
        $data['supports_booking'] = $request->boolean('supports_booking');
        $data['settings'] = $this->decodeSettings($request);
        unset($data['settings_json']);

        if (($data['parent_id'] ?? null) == $category->id) {
            return back()
                ->withErrors(['parent_id' => 'لا يمكن أن يكون القسم أباً لنفسه.'])
                ->withInput();
        }

        $category->update($data);

        return redirect()
            ->route('admin.categories.edit', $category)
            ->with('success', 'تم تعديل القسم بنجاح.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        if ($category->listings()->exists()) {
            return back()->withErrors(['category' => 'لا يمكن حذف قسم يحتوي على خدمات. يمكن تعطيله بدلاً من ذلك.']);
        }

        $category->delete();

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'تم حذف القسم بنجاح.');
    }

    private function validatedData(Request $request, ?Category $category = null): array
    {
        return $request->validate([
            'parent_id' => ['nullable', 'exists:categories,id'],
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('categories', 'slug')->ignore($category?->id)],
            'group_key' => ['nullable', 'string', 'max:80'],
            'description_ar' => ['nullable', 'string'],
            'description_en' => ['nullable', 'string'],
            'icon' => ['nullable', 'string', 'max:100'],
            'settings_json' => ['nullable', 'json'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);
    }

    private function decodeSettings(Request $request): ?array
    {
        if (! $request->filled('settings_json')) {
            return null;
        }

        return json_decode($request->input('settings_json'), true);
    }

    private function parentOptions(?Category $current = null)
    {
        return Category::query()
            ->when($current, fn ($query) => $query->whereKeyNot($current->id))
            ->orderBy('group_key')
            ->orderBy('sort_order')
            ->get(['id', 'name_ar', 'slug', 'group_key']);
    }

    private function makeSlug(string $name): string
    {
        $slug = Str::slug($name);

        return ($slug ?: 'category').'-'.Str::lower(Str::random(6));
    }
}
