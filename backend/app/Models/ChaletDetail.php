<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChaletDetail extends Model
{
    use HasFactory;

    protected $primaryKey = 'listing_id';

    public $incrementing = false;

    protected $fillable = [
        'listing_id',
        'area_size',
        'rooms_count',
        'bathrooms_count',
        'max_guests',
        'has_pool',
        'pool_is_heated',
    ];

    protected $casts = [
        'area_size' => 'integer',
        'rooms_count' => 'integer',
        'bathrooms_count' => 'integer',
        'max_guests' => 'integer',
        'has_pool' => 'boolean',
        'pool_is_heated' => 'boolean',
    ];

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }
}
