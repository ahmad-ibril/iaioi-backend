<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class WantedRequest extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'user_id',
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
        'request_type',
    ];

    protected $casts = [
        'budget' => 'decimal:2',
        'needed_date' => 'date:Y-m-d',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(City::class, 'region_id');
    }

    public function media(): HasMany
    {
        return $this->hasMany(WantedRequestMedia::class)->orderBy('sort_order');
    }

    public function attributes(): HasMany
    {
        return $this->hasMany(WantedRequestAttributeValue::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (! $term) {
            return $query;
        }

        return $query->where(function (Builder $query) use ($term): void {
            $query
                ->where('title', 'like', "%{$term}%")
                ->orWhere('description', 'like', "%{$term}%")
                ->orWhere('area_name', 'like', "%{$term}%");
        });
    }
}
