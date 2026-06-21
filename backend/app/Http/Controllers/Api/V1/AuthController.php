<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:40', 'unique:users,phone'],
            'whatsapp' => ['nullable', 'string', 'max:40'],
            'account_type' => ['nullable', Rule::in(array_keys(config('account_types.types')))],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $accountType = $data['account_type'] ?? config('account_types.default');

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'whatsapp' => $data['whatsapp'] ?? null,
            'password' => $data['password'],
            'auth_provider' => 'email',
            'account_type' => $accountType,
            'verification_status' => 'none',
            'role' => 'user',
            'status' => 'active',
        ]);

        return response()->json([
            'token' => $user->createToken('user-app')->plainTextToken,
            'user' => new UserResource($user),
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::query()
            ->where('email', $credentials['email'])
            ->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['بيانات الدخول غير صحيحة.'],
            ]);
        }

        if ($user->status !== 'active') {
            throw ValidationException::withMessages([
                'email' => ['هذا الحساب غير مفعل.'],
            ]);
        }

        return response()->json([
            'token' => $user->createToken('user-app')->plainTextToken,
            'user' => new UserResource($user),
        ]);
    }

    public function google(Request $request): JsonResponse
    {
        $data = $request->validate([
            'id_token' => ['required', 'string'],
            'account_type' => ['nullable', Rule::in(array_keys(config('account_types.types')))],
        ]);

        $googleUser = $this->verifyGoogleIdToken($data['id_token']);
        $email = $googleUser['email'] ?? null;

        if (! $email || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw ValidationException::withMessages([
                'id_token' => ['تعذر قراءة البريد الإلكتروني من حساب Google.'],
            ]);
        }

        if (($googleUser['email_verified'] ?? 'false') !== 'true' && ($googleUser['email_verified'] ?? false) !== true) {
            throw ValidationException::withMessages([
                'id_token' => ['يجب أن يكون بريد Google مؤكدا.'],
            ]);
        }

        $user = User::query()->where('email', $email)->first();

        if (! $user) {
            $user = User::create([
                'name' => $googleUser['name'] ?? explode('@', $email)[0],
                'email' => $email,
                'google_id' => $googleUser['sub'] ?? null,
                'auth_provider' => 'google',
                'avatar_path' => $googleUser['picture'] ?? null,
                'email_verified_at' => now(),
                'password' => Str::random(48),
                'account_type' => $data['account_type'] ?? config('account_types.default'),
                'verification_status' => 'none',
                'role' => 'user',
                'status' => 'active',
            ]);
        } else {
            $user->update([
                'google_id' => $user->google_id ?: ($googleUser['sub'] ?? null),
                'auth_provider' => $user->auth_provider === 'email' ? 'google' : $user->auth_provider,
                'avatar_path' => $user->avatar_path ?: ($googleUser['picture'] ?? null),
                'email_verified_at' => $user->email_verified_at ?? now(),
            ]);
        }

        if ($user->status !== 'active') {
            throw ValidationException::withMessages([
                'email' => ['هذا الحساب غير مفعل.'],
            ]);
        }

        return response()->json([
            'token' => $user->createToken('user-app')->plainTextToken,
            'user' => new UserResource($user->refresh()),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['user' => new UserResource($request->user())]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40', Rule::unique('users', 'phone')->ignore($user->id)],
            'whatsapp' => ['nullable', 'string', 'max:40'],
        ]);

        $user->update($data);

        return response()->json(['user' => new UserResource($user->refresh())]);
    }

    public function accountTypes(): JsonResponse
    {
        $types = collect(config('account_types.types'))
            ->map(fn (array $config, string $key) => [
                'value' => $key,
                'label_ar' => $config['label_ar'] ?? $key,
                'requires_verification' => (bool) ($config['requires_verification'] ?? false),
            ])
            ->values();

        return response()->json(['data' => $types]);
    }

    public function updateAccountType(Request $request): JsonResponse
    {
        $data = $request->validate([
            'account_type' => ['required', Rule::in(array_keys(config('account_types.types')))],
        ]);

        $user = $request->user();
        $accountTypeChanged = $user->account_type !== $data['account_type'];
        $requiresVerification = (bool) config("account_types.types.{$data['account_type']}.requires_verification", false);

        $payload = ['account_type' => $data['account_type']];

        if ($accountTypeChanged || ! $requiresVerification) {
            $payload['verification_status'] = 'none';
        }

        $user->update($payload);

        return response()->json(['user' => new UserResource($user->refresh())]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json(['message' => 'تم تسجيل الخروج.']);
    }

    private function verifyGoogleIdToken(string $idToken): array
    {
        try {
            $response = Http::timeout(10)->get('https://oauth2.googleapis.com/tokeninfo', [
                'id_token' => $idToken,
            ]);
        } catch (\Throwable) {
            throw ValidationException::withMessages([
                'id_token' => ['تعذر التحقق من Google حاليا.'],
            ]);
        }

        if (! $response->ok()) {
            throw ValidationException::withMessages([
                'id_token' => ['رمز Google غير صالح.'],
            ]);
        }

        return $response->json();
    }
}
