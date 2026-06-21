<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class AdminUserController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $users = User::query()
            ->withCount(['listings', 'bookingRequests'])
            ->when($request->filled('q'), function ($query) use ($request): void {
                $term = $request->string('q')->toString();
                $query->where(function ($query) use ($term): void {
                    $query
                        ->where('name', 'like', "%{$term}%")
                        ->orWhere('email', 'like', "%{$term}%")
                        ->orWhere('phone', 'like', "%{$term}%");
                });
            })
            ->when($request->filled('role'), fn ($query) => $query->where('role', $request->input('role')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->input('status')))
            ->latest('id')
            ->paginate(min($request->integer('per_page', 50), 100));

        return UserResource::collection($users);
    }

    public function update(Request $request, User $user): UserResource
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:40', Rule::unique('users', 'phone')->ignore($user->id)],
            'whatsapp' => ['nullable', 'string', 'max:40'],
            'role' => ['sometimes', Rule::in(['admin', 'owner', 'customer', 'user'])],
            'status' => ['sometimes', Rule::in(['active', 'inactive'])],
            'account_type' => ['nullable', 'string', 'max:80'],
            'verification_status' => ['nullable', Rule::in(['none', 'pending', 'verified', 'rejected'])],
        ]);

        if (($data['role'] ?? null) === 'user') {
            $data['role'] = 'customer';
        }

        $user->update($data);

        return new UserResource($user->refresh());
    }

    public function destroy(User $user): JsonResponse
    {
        abort_if($user->role === 'admin' && User::query()->where('role', 'admin')->count() <= 1, 422, 'Cannot delete the last admin.');

        $user->delete();

        return response()->json(['message' => 'User deleted.']);
    }
}
