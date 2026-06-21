<?php

namespace App\Models;

use App\Support\PublicStorageUrl;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HotelRoomImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'hotel_room_id',
        'path',
        'alt_text_ar',
        'alt_text_en',
        'sort_order',
        'is_cover',
    ];

    protected $appends = ['url'];

    protected $casts = [
        'sort_order' => 'integer',
        'is_cover' => 'boolean',
    ];

    public function room(): BelongsTo
    {
        return $this->belongsTo(HotelRoom::class, 'hotel_room_id');
    }

    public function getUrlAttribute(): string
    {
        return PublicStorageUrl::fromPath($this->path) ?? '';
    }
}
