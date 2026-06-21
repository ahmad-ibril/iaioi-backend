<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\WantedRequestResource;
use App\Models\Category;
use App\Models\CategoryFilter;
use App\Models\WantedRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class WantedRequestController extends Controller
{
    private const RELATIONS = [
        'user',
        'category',
        'category.filters',
        'region',
        'media',
        'attributes.filter',
    ];

    public function index(Request $request): AnonymousResourceCollection
    {
        $query = WantedRequest::query()
            ->active()
            ->with(self::RELATIONS)
            ->search($request->string('q')->toString());

        $this->applyFilters($query, $request);
        $this->applyDynamicFilters($query, $request);

        return WantedRequestResource::collection(
            $query->latest('id')->paginate(min($request->integer('per_page', 15), 50)),
        );
    }

    public function myIndex(Request $request): AnonymousResourceCollection
    {
        $query = WantedRequest::query()
            ->where('user_id', $request->user()->id)
            ->with(self::RELATIONS)
            ->search($request->string('q')->toString())
            ->when($request->filled('status'), fn (Builder $query) => $query->where('status', $request->input('status')));

        $this->applyFilters($query, $request);
        $this->applyDynamicFilters($query, $request);

        return WantedRequestResource::collection(
            $query->latest('id')->paginate(min($request->integer('per_page', 15), 50)),
        );
    }

    public function store(Request $request): WantedRequestResource
    {
        $data = $this->validatedData($request);

        $wantedRequest = DB::transaction(function () use ($request, $data): WantedRequest {
            $user = $request->user();

            $wantedRequest = WantedRequest::create([
                'user_id' => $user->id,
                'category_id' => $data['category_id'],
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'budget' => $data['budget'] ?? null,
                'region_id' => $data['region_id'] ?? null,
                'area_name' => $data['area_name'] ?? null,
                'needed_date' => $data['needed_date'] ?? null,
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
                'phone' => $data['phone'] ?? $user->phone,
                'whatsapp' => $data['whatsapp'] ?? $user->whatsapp,
                'status' => $data['status'] ?? 'active',
                'request_type' => 'wanted',
            ]);

            $this->syncAttributes($wantedRequest, $data['attributes'] ?? []);
            $this->storeUploadedMedia($wantedRequest, $request);

            return $wantedRequest;
        });

        return new WantedRequestResource($wantedRequest->load(self::RELATIONS));
    }

    public function show(Request $request, WantedRequest $wantedRequest): WantedRequestResource
    {
        $user = $request->user();
        abort_unless($wantedRequest->status === 'active' || $user?->id === $wantedRequest->user_id, 404);

        return new WantedRequestResource($wantedRequest->load(self::RELATIONS));
    }

    public function update(Request $request, WantedRequest $wantedRequest): WantedRequestResource
    {
        $this->authorizeOwnership($request, $wantedRequest);
        $data = $this->validatedData($request, true);

        DB::transaction(function () use ($request, $wantedRequest, $data): void {
            $payload = [];

            foreach ([
                'category_id',
                'title',
                'description',
                'budget',
                'region_id',
                'area_name',
                'needed_date',
                'latitude',
                'longitude',
                'phone',
                'whatsapp',
                'status',
            ] as $field) {
                if (array_key_exists($field, $data)) {
                    $payload[$field] = $data[$field];
                }
            }

            $wantedRequest->update($payload);

            if (array_key_exists('attributes', $data)) {
                $this->syncAttributes($wantedRequest->refresh(), $data['attributes'] ?? []);
            }

            $this->storeUploadedMedia($wantedRequest, $request);
        });

        return new WantedRequestResource($wantedRequest->refresh()->load(self::RELATIONS));
    }

    public function destroy(Request $request, WantedRequest $wantedRequest): JsonResponse
    {
        $this->authorizeOwnership($request, $wantedRequest);
        $wantedRequest->delete();

        return response()->json(['message' => 'تم حذف الطلب بنجاح.']);
    }

    private function validatedData(Request $request, bool $isUpdate = false): array
    {
        $required = $isUpdate ? 'sometimes' : 'required';

        return $request->validate([
            'category_id' => [$required, 'exists:categories,id'],
            'title' => [$required, 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'budget' => ['nullable', 'numeric', 'min:0'],
            'region_id' => ['nullable', 'exists:cities,id'],
            'area_name' => ['nullable', 'string', 'max:255'],
            'needed_date' => ['nullable', 'date'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'phone' => ['nullable', 'string', 'max:40'],
            'whatsapp' => ['nullable', 'string', 'max:40'],
            'status' => ['nullable', Rule::in(['active', 'pending'])],
            'attributes' => ['nullable', 'array'],
            'uploaded_media' => ['nullable', 'array'],
            'uploaded_media.*' => ['file', 'mimes:jpg,jpeg,png,webp,gif,mp4,mov,webm,avi', 'max:51200'],
        ]);
    }

    private function applyFilters(Builder $query, Request $request): void
    {
        $query
            ->when($request->filled('category_id'), fn (Builder $query) => $query->where('category_id', $request->integer('category_id')))
            ->when($request->filled('category'), function (Builder $query) use ($request): void {
                $category = $request->input('category');

                $query->whereHas('category', function (Builder $query) use ($category): void {
                    is_numeric($category)
                        ? $query->whereKey($category)
                        : $query->where('slug', $category);
                });
            })
            ->when($request->filled('region_id'), fn (Builder $query) => $query->where('region_id', $request->integer('region_id')))
            ->when($request->filled('area'), fn (Builder $query) => $query->where('area_name', 'like', "%{$request->string('area')->toString()}%"))
            ->when($request->filled('min_budget'), fn (Builder $query) => $query->where('budget', '>=', $request->input('min_budget')))
            ->when($request->filled('max_budget'), fn (Builder $query) => $query->where('budget', '<=', $request->input('max_budget')))
            ->when($request->filled('needed_date'), fn (Builder $query) => $query->whereDate('needed_date', $request->input('needed_date')));
    }

    private function applyDynamicFilters(Builder $query, Request $request): void
    {
        $filters = $request->input('filters', []);

        if (! is_array($filters) || $filters === []) {
            return;
        }

        $category = $this->resolveCategory($request);
        $filterDefinitions = CategoryFilter::query()
            ->filterable()
            ->when($category, fn (Builder $query) => $query->where('category_id', $category->id))
            ->get()
            ->keyBy('key');

        foreach ($filters as $key => $value) {
            $definition = $filterDefinitions->get($key);

            if (! $definition || $value === null || $value === '') {
                continue;
            }

            $query->whereHas('attributes', function (Builder $query) use ($definition, $key, $value): void {
                $query->where('key', $key);

                switch ($definition->input_type) {
                    case 'number':
                    case 'rating':
                        if (is_array($value)) {
                            $query
                                ->when(isset($value['min']), fn (Builder $query) => $query->where('value_number', '>=', $value['min']))
                                ->when(isset($value['max']), fn (Builder $query) => $query->where('value_number', '<=', $value['max']));
                        } else {
                            $query->where('value_number', $value);
                        }
                        break;
                    case 'boolean':
                        $query->where('value_boolean', filter_var($value, FILTER_VALIDATE_BOOLEAN));
                        break;
                    case 'date':
                        $query->whereDate('value_date', $value);
                        break;
                    case 'time':
                        $query->whereTime('value_time', $value);
                        break;
                    case 'multi_select':
                        foreach ((array) $value as $item) {
                            $query->orWhereJsonContains('value_json', $item);
                        }
                        break;
                    default:
                        $query->where('value_text', $value);
                }
            });
        }
    }

    private function resolveCategory(Request $request): ?Category
    {
        $category = $request->input('category_id') ?? $request->input('category');

        if (! $category) {
            return null;
        }

        return Category::query()
            ->when(
                is_numeric($category),
                fn (Builder $query) => $query->whereKey($category),
                fn (Builder $query) => $query->where('slug', $category),
            )
            ->first();
    }

    private function syncAttributes(WantedRequest $wantedRequest, array $attributes): void
    {
        $filters = CategoryFilter::query()
            ->where('category_id', $wantedRequest->category_id)
            ->orderBy('sort_order')
            ->get();

        foreach ($filters as $filter) {
            if ($filter->is_required && ! array_key_exists($filter->key, $attributes)) {
                throw ValidationException::withMessages([
                    "attributes.{$filter->key}" => ['هذا الحقل مطلوب للقسم المختار.'],
                ]);
            }

            if (! array_key_exists($filter->key, $attributes)) {
                continue;
            }

            $value = $attributes[$filter->key];

            if ($value === null || $value === '') {
                $wantedRequest->attributes()->where('key', $filter->key)->delete();
                continue;
            }

            $wantedRequest->attributes()->updateOrCreate(
                ['key' => $filter->key],
                array_merge(['category_filter_id' => $filter->id], $this->attributeValuePayload($filter, $value)),
            );
        }
    }

    private function attributeValuePayload(CategoryFilter $filter, mixed $value): array
    {
        $payload = [
            'value_text' => null,
            'value_number' => null,
            'value_boolean' => null,
            'value_date' => null,
            'value_time' => null,
            'value_json' => null,
        ];

        switch ($filter->input_type) {
            case 'number':
            case 'rating':
                $payload['value_number'] = $value;
                break;
            case 'boolean':
                $payload['value_boolean'] = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                break;
            case 'date':
                $payload['value_date'] = $value;
                break;
            case 'time':
                $payload['value_time'] = $value;
                break;
            case 'multi_select':
                $payload['value_json'] = is_array($value) ? array_values($value) : [$value];
                break;
            default:
                $payload['value_text'] = is_array($value)
                    ? json_encode($value, JSON_UNESCAPED_UNICODE)
                    : (string) $value;
        }

        return $payload;
    }

    private function storeUploadedMedia(WantedRequest $wantedRequest, Request $request): void
    {
        if (! $request->hasFile('uploaded_media')) {
            return;
        }

        $nextSortOrder = (int) $wantedRequest->media()->max('sort_order') + 1;

        foreach ($request->file('uploaded_media') as $index => $file) {
            $mimeType = (string) $file->getMimeType();

            $wantedRequest->media()->create([
                'media_type' => str_starts_with($mimeType, 'video/') ? 'video' : 'image',
                'path' => $file->store("wanted-requests/{$wantedRequest->id}", 'public'),
                'mime_type' => $mimeType,
                'sort_order' => $nextSortOrder + $index,
            ]);
        }
    }

    private function authorizeOwnership(Request $request, WantedRequest $wantedRequest): void
    {
        abort_unless($wantedRequest->user_id === $request->user()->id, 404);
    }
}
