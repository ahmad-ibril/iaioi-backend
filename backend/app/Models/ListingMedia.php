<?php

namespace App\Models;

use App\Support\PublicStorageUrl;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ListingMedia extends Model
{
    use HasFactory;

    protected $table = 'listing_media';

    protected $fillable = [
        'listing_id',
        'media_type',
        'path',
        'mime_type',
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

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }

    public function getUrlAttribute(): string
    {
        return PublicStorageUrl::fromPath($this->path) ?? '';
    }
}
