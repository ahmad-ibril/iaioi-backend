<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ListingCalendarDate extends Model
{
    use HasFactory;

    protected $fillable = [
        'listing_id',
        'date',
        'status',
        'price_override',
        'note',
    ];

    protected $casts = [
        'date' => 'date:Y-m-d',
        'price_override' => 'decimal:2',
    ];

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }
}
