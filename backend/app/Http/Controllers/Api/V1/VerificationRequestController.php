<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\VerificationRequestResource;
use App\Models\VerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class VerificationRequestController extends Controller
{
    private const RELATIONS = ['user', 'category'];

    private const FILE_FIELDS = [
        'national_id_image',
        'commercial_registration_image',
        'business_license_image',
        'ownership_or_rent_contract_image',
    ];

    public function index(Request $request): AnonymousResourceCollection
    {
        $requests = VerificationRequest::query()
            ->where('user_id', $request->user()->id)
            ->with(self::RELATIONS)
            ->latest()
            ->paginate(min($request->integer('per_page', 15), 50));

        return VerificationRequestResource::collection($requests);
    }

    public function store(Request $request): VerificationRequestResource
    {
        $data = $this->validatedData($request);

        $verificationRequest = DB::transaction(function () use ($request, $data): VerificationRequest {
            $user = $request->user();
            $payload = array_merge($data, [
                'user_id' => $user->id,
                'status' => 'pending',
            ]);

            foreach (self::FILE_FIELDS as $field) {
                if ($request->hasFile($field)) {
                    $payload[$field] = $request->file($field)->store("verification-requests/{$user->id}", 'public');
                }
            }

            $verificationRequest = VerificationRequest::create($payload);
            $user->update(['verification_status' => 'pending']);

            return $verificationRequest;
        });

        return new VerificationRequestResource($verificationRequest->load(self::RELATIONS));
    }

    public function show(Request $request, VerificationRequest $verificationRequest): VerificationRequestResource
    {
        $this->authorizeOwnership($request, $verificationRequest);

        return new VerificationRequestResource($verificationRequest->load(self::RELATIONS));
    }

    public function update(Request $request, VerificationRequest $verificationRequest): VerificationRequestResource
    {
        $this->authorizeOwnership($request, $verificationRequest);
        abort_unless($verificationRequest->status === 'pending', 422, 'لا يمكن تعديل طلب توثيق تمت مراجعته.');

        $data = $this->validatedData($request, true);

        DB::transaction(function () use ($request, $verificationRequest, $data): void {
            $payload = $data;

            foreach (self::FILE_FIELDS as $field) {
                if ($request->hasFile($field)) {
                    $payload[$field] = $request->file($field)->store("verification-requests/{$request->user()->id}", 'public');
                }
            }

            $verificationRequest->update($payload);
        });

        return new VerificationRequestResource($verificationRequest->refresh()->load(self::RELATIONS));
    }

    private function validatedData(Request $request, bool $isUpdate = false): array
    {
        $required = $isUpdate ? 'sometimes' : 'required';

        return $request->validate([
            'category_id' => ['nullable', 'exists:categories,id'],
            'business_name' => [$required, 'string', 'max:255'],
            'business_type' => [$required, 'string', 'max:255'],
            'commercial_registration_number' => ['nullable', 'string', 'max:255'],
            'license_number' => ['nullable', 'string', 'max:255'],
            'business_location_latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'business_location_longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'national_id_image' => ['nullable', 'image', 'max:10240'],
            'commercial_registration_image' => ['nullable', 'image', 'max:10240'],
            'business_license_image' => ['nullable', 'image', 'max:10240'],
            'ownership_or_rent_contract_image' => ['nullable', 'image', 'max:10240'],
        ]);
    }

    private function authorizeOwnership(Request $request, VerificationRequest $verificationRequest): void
    {
        abort_unless($verificationRequest->user_id === $request->user()->id, 404);
    }
}
