<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Schema;

class Listing extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'category_id',
        'country_id',
        'city_id',
        'owner_user_id',
        'title_ar',
        'title_en',
        'slug',
        'description_ar',
        'description_en',
        'latitude',
        'longitude',
        'area_name_ar',
        'area_name_en',
        'address_ar',
        'address_en',
        'phone',
        'whatsapp',
        'base_price',
        'currency_code',
        'price_unit',
        'status',
        'listing_type',
        'is_featured',
        'featured_until',
        'views_count',
        'published_at',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'base_price' => 'decimal:2',
        'is_featured' => 'boolean',
        'featured_until' => 'datetime',
        'views_count' => 'integer',
        'published_at' => 'datetime',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(ListingImage::class)->orderBy('sort_order');
    }

    public function media(): HasMany
    {
        return $this->hasMany(ListingMedia::class)->orderBy('sort_order');
    }

    public function features(): HasMany
    {
        return $this->hasMany(ListingFeature::class)->orderBy('sort_order');
    }

    public function attributes(): HasMany
    {
        return $this->hasMany(ListingAttributeValue::class);
    }

    public function calendarDates(): HasMany
    {
        return $this->hasMany(ListingCalendarDate::class);
    }

    public function availabilitySlots(): HasMany
    {
        return $this->hasMany(ListingAvailabilitySlot::class)
            ->orderBy('date')
            ->orderBy('start_time')
            ->orderBy('id');
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    public function bookingRequests(): HasMany
    {
        return $this->hasMany(BookingRequest::class);
    }

    public function chaletDetail(): HasOne
    {
        return $this->hasOne(ChaletDetail::class);
    }

    public function sportsFieldDetail(): HasOne
    {
        return $this->hasOne(SportsFieldDetail::class);
    }

    public function weddingHallDetail(): HasOne
    {
        return $this->hasOne(WeddingHallDetail::class);
    }

    public function weddingSupplyDetail(): HasOne
    {
        return $this->hasOne(WeddingSupplyDetail::class);
    }

    public function carRentalDetail(): HasOne
    {
        return $this->hasOne(CarRentalDetail::class);
    }

    public function busRentalDetail(): HasOne
    {
        return $this->hasOne(BusRentalDetail::class);
    }

    public function hotelDetail(): HasOne
    {
        return $this->hasOne(HotelDetail::class);
    }

    public function hotelRooms(): HasMany
    {
        return $this->hasMany(HotelRoom::class, 'hotel_listing_id');
    }

    public function tourismProgramDetail(): HasOne
    {
        return $this->hasOne(TourismProgramDetail::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (! $term) {
            return $query;
        }

        return $query->where(function (Builder $query) use ($term): void {
            $query
                ->where('title_ar', 'like', "%{$term}%")
                ->orWhere('title_en', 'like', "%{$term}%")
                ->orWhere('description_ar', 'like', "%{$term}%")
                ->orWhere('description_en', 'like', "%{$term}%")
                ->orWhere('area_name_ar', 'like', "%{$term}%")
                ->orWhere('area_name_en', 'like', "%{$term}%");
        });
    }

    public function scopeAvailableBetween(Builder $query, ?string $from, ?string $to): Builder
    {
        if (! $from || ! $to) {
            return $query;
        }

        $query->whereDoesntHave('calendarDates', function (Builder $query) use ($from, $to): void {
            $query
                ->whereBetween('date', [$from, $to])
                ->whereIn('status', ['booked', 'blocked']);
        });

        if (Schema::hasTable('listing_availability_slots')) {
            $query->whereDoesntHave('availabilitySlots', function (Builder $query) use ($from, $to): void {
                $query
                    ->whereBetween('date', [$from, $to])
                    ->whereIn('status', ['reserved', 'unavailable']);
            });
        }

        return $query;
    }

    public function scopeWithDistance(Builder $query, float $latitude, float $longitude): Builder
    {
        return $query
            ->select('listings.*')
            ->selectRaw(
                '(6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) as distance_km',
                [$latitude, $longitude, $latitude],
            )
            ->whereNotNull('latitude')
            ->whereNotNull('longitude');
    }
}
