<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class HotelRoom extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'hotel_listing_id',
        'name_ar',
        'name_en',
        'room_type',
        'description_ar',
        'description_en',
        'capacity_adults',
        'capacity_children',
        'price_per_night',
        'currency_code',
        'total_rooms',
        'is_active',
    ];

    protected $casts = [
        'capacity_adults' => 'integer',
        'capacity_children' => 'integer',
        'price_per_night' => 'decimal:2',
        'total_rooms' => 'integer',
        'is_active' => 'boolean',
    ];

    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Listing::class, 'hotel_listing_id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(HotelRoomImage::class)->orderBy('sort_order');
    }

    public function calendarDates(): HasMany
    {
        return $this->hasMany(HotelRoomCalendarDate::class);
    }
}
