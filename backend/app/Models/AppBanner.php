<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppBanner extends Model
{
    protected $fillable = [
        'title_ar',
        'title_en',
        'subtitle_ar',
        'subtitle_en',
        'image_url',
        'link_url',
        'placement',
        'sort_order',
        'starts_at',
        'ends_at',
        'is_active',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'is_active' => 'boolean',
    ];
}
