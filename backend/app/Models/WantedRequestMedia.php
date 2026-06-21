<?php

namespace App\Models;

use App\Support\PublicStorageUrl;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WantedRequestMedia extends Model
{
    use HasFactory;

    protected $fillable = [
        'wanted_request_id',
        'media_type',
        'path',
        'mime_type',
        'sort_order',
    ];

    protected $appends = ['url'];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function wantedRequest(): BelongsTo
    {
        return $this->belongsTo(WantedRequest::class);
    }

    public function getUrlAttribute(): string
    {
        return PublicStorageUrl::fromPath($this->path) ?? '';
    }
}
