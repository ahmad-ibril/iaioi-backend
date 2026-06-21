<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $accountType = $this->account_type ?: config('account_types.default');
        $accountTypeConfig = config("account_types.types.{$accountType}", []);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'phone' => $this->phone,
            'whatsapp' => $this->whatsapp,
            'email' => $this->email,
            'account_type' => $accountType,
            'account_type_label' => $accountTypeConfig['label_ar'] ?? $accountType,
            'requires_verification' => (bool) ($accountTypeConfig['requires_verification'] ?? false),
            'verification_status' => $this->verification_status ?? 'none',
            'role' => $this->role,
            'status' => $this->status,
        ];
    }
}
