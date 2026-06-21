<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ListingAvailabilitySlot extends Model
{
    use HasFactory;

    protected $fillable = [
        'listing_id',
        'date',
        'slot_name',
        'start_time',
        'end_time',
        'price',
        'status',
    ];

    protected $casts = [
        'date' => 'date:Y-m-d',
        'price' => 'decimal:2',
    ];

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }

    public function bookingRequests(): HasMany
    {
        return $this->hasMany(BookingRequest::class, 'availability_slot_id');
    }

    public function scopeForMonth($query, ?string $month)
    {
        if (! $month) {
            return $query;
        }

        return $query->whereDate('date', '>=', "{$month}-01")
            ->whereDate('date', '<=', date('Y-m-t', strtotime("{$month}-01")));
    }
}
