<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CarRentalDetail extends Model
{
    use HasFactory;

    protected $primaryKey = 'listing_id';

    public $incrementing = false;

    protected $fillable = [
        'listing_id',
        'car_type',
        'brand',
        'model',
        'year',
        'seats_count',
        'with_driver',
        'transmission',
    ];

    protected $casts = [
        'year' => 'integer',
        'seats_count' => 'integer',
        'with_driver' => 'boolean',
    ];

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }
}
