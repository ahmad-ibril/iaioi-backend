<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'group',
        'value_type',
    ];

    public function getTypedValueAttribute(): mixed
    {
        return match ($this->value_type) {
            'boolean' => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
            'number' => is_numeric($this->value) ? (float) $this->value : null,
            'json' => $this->value ? json_decode($this->value, true) : null,
            default => $this->value,
        };
    }
}
