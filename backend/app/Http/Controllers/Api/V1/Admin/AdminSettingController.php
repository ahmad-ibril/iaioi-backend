<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminSettingController extends Controller
{
    public function index(): JsonResponse
    {
        $settings = AppSetting::query()
            ->orderBy('group')
            ->orderBy('key')
            ->get()
            ->mapWithKeys(fn (AppSetting $setting) => [$setting->key => [
                'value' => $setting->typed_value,
                'group' => $setting->group,
                'value_type' => $setting->value_type,
            ]]);

        return response()->json(['data' => $settings]);
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'settings' => ['required', 'array'],
            'settings.*.value' => ['nullable'],
            'settings.*.group' => ['nullable', 'string', 'max:80'],
            'settings.*.value_type' => ['nullable', 'string', 'max:32'],
        ]);

        foreach ($data['settings'] as $key => $setting) {
            AppSetting::updateOrCreate(
                ['key' => $key],
                [
                    'value' => $this->stringValue($setting['value'] ?? null, $setting['value_type'] ?? 'string'),
                    'group' => $setting['group'] ?? 'general',
                    'value_type' => $setting['value_type'] ?? 'string',
                ],
            );
        }

        return $this->index();
    }

    private function stringValue(mixed $value, string $type): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($type === 'json' || is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        if ($type === 'boolean') {
            return filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false';
        }

        return (string) $value;
    }
}
