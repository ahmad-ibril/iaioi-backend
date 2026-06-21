<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WeddingHallDetail extends Model
{
    use HasFactory;

    protected $primaryKey = 'listing_id';

    public $incrementing = false;

    protected $fillable = [
        'listing_id',
        'capacity',
        'hall_type',
        'has_parking',
        'has_catering',
    ];

    protected $casts = [
        'capacity' => 'integer',
        'has_parking' => 'boolean',
        'has_catering' => 'boolean',
    ];

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }
}
