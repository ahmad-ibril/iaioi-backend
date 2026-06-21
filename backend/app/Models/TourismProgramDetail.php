<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TourismProgramDetail extends Model
{
    use HasFactory;

    protected $primaryKey = 'listing_id';

    public $incrementing = false;

    protected $fillable = [
        'listing_id',
        'destination_country',
        'destination_city',
        'departure_country',
        'departure_city',
        'duration_days',
        'trip_date',
        'trip_type',
        'seats_available',
        'included_services',
        'flight_times',
    ];

    protected $casts = [
        'duration_days' => 'integer',
        'trip_date' => 'date:Y-m-d',
        'seats_available' => 'integer',
        'included_services' => 'array',
        'flight_times' => 'array',
    ];

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }
}
