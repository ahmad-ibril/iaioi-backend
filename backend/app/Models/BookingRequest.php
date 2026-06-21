<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'listing_id',
        'availability_slot_id',
        'status',
        'date_from',
        'date_to',
        'quantity',
        'customer_name',
        'customer_phone',
        'contact_name',
        'contact_phone',
        'notes',
        'admin_notes',
    ];

    protected $casts = [
        'date_from' => 'date',
        'date_to' => 'date',
        'quantity' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }

    public function availabilitySlot(): BelongsTo
    {
        return $this->belongsTo(ListingAvailabilitySlot::class, 'availability_slot_id');
    }
}
