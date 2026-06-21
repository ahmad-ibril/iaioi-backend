<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VerificationRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'category_id',
        'business_name',
        'business_type',
        'commercial_registration_number',
        'license_number',
        'national_id_image',
        'commercial_registration_image',
        'business_license_image',
        'ownership_or_rent_contract_image',
        'business_location_latitude',
        'business_location_longitude',
        'notes',
        'status',
        'admin_notes',
        'reviewed_at',
    ];

    protected $casts = [
        'business_location_latitude' => 'decimal:7',
        'business_location_longitude' => 'decimal:7',
        'reviewed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
