<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\VerificationRequestResource;
use App\Models\VerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AdminVerificationRequestController extends Controller
{
    private const RELATIONS = ['user', 'category'];

    public function index(Request $request): AnonymousResourceCollection
    {
        $requests = VerificationRequest::query()
            ->with(self::RELATIONS)
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->input('status')))
            ->latest()
            ->paginate(min($request->integer('per_page', 15), 50));

        return VerificationRequestResource::collection($requests);
    }

    public function show(VerificationRequest $verificationRequest): VerificationRequestResource
    {
        return new VerificationRequestResource($verificationRequest->load(self::RELATIONS));
    }

    public function update(Request $request, VerificationRequest $verificationRequest): VerificationRequestResource
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['pending', 'approved', 'rejected'])],
            'admin_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($verificationRequest, $data): void {
            $verificationRequest->update([
                'status' => $data['status'],
                'admin_notes' => $data['admin_notes'] ?? $verificationRequest->admin_notes,
                'reviewed_at' => $data['status'] === 'pending' ? null : now(),
            ]);

            $verificationStatus = match ($data['status']) {
                'approved' => 'verified',
                'rejected' => 'rejected',
                default => 'pending',
            };

            $verificationRequest->user?->update(['verification_status' => $verificationStatus]);
        });

        return new VerificationRequestResource($verificationRequest->refresh()->load(self::RELATIONS));
    }
}
