<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HotelRoomCalendarDate extends Model
{
    use HasFactory;

    protected $fillable = [
        'hotel_room_id',
        'date',
        'status',
        'available_quantity',
        'price_override',
    ];

    protected $casts = [
        'date' => 'date:Y-m-d',
        'available_quantity' => 'integer',
        'price_override' => 'decimal:2',
    ];

    public function room(): BelongsTo
    {
        return $this->belongsTo(HotelRoom::class, 'hotel_room_id');
    }
}
