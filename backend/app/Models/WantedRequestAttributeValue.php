<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WantedRequestAttributeValue extends Model
{
    use HasFactory;

    protected $fillable = [
        'wanted_request_id',
        'category_filter_id',
        'key',
        'value_text',
        'value_number',
        'value_boolean',
        'value_date',
        'value_time',
        'value_json',
    ];

    protected $casts = [
        'value_number' => 'decimal:2',
        'value_boolean' => 'boolean',
        'value_date' => 'date:Y-m-d',
        'value_json' => 'array',
    ];

    public function wantedRequest(): BelongsTo
    {
        return $this->belongsTo(WantedRequest::class);
    }

    public function filter(): BelongsTo
    {
        return $this->belongsTo(CategoryFilter::class, 'category_filter_id');
    }

    public function getValueAttribute(): mixed
    {
        return match ($this->filter?->input_type) {
            'number', 'rating' => $this->value_number,
            'boolean' => $this->value_boolean,
            'date' => $this->value_date?->format('Y-m-d'),
            'time' => $this->value_time,
            'multi_select' => $this->value_json,
            default => $this->value_text ?? $this->value_json,
        };
    }
}
