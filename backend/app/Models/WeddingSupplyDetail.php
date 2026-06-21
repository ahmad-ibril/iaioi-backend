<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WeddingSupplyDetail extends Model
{
    use HasFactory;

    protected $primaryKey = 'listing_id';

    public $incrementing = false;

    protected $fillable = [
        'listing_id',
        'supply_type',
        'quantity_available',
        'package_items',
    ];

    protected $casts = [
        'quantity_available' => 'integer',
        'package_items' => 'array',
    ];

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }
}
