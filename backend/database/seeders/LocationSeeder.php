<?php

namespace Database\Seeders;

use App\Models\Country;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    public function run(): void
    {
        $jordan = Country::query()->updateOrCreate(
            ['iso_code' => 'JO'],
            ['name_ar' => 'الأردن', 'name_en' => 'Jordan', 'phone_code' => '+962', 'is_active' => true],
        );

        $cities = [
            ['name_ar' => 'عمّان', 'name_en' => 'Amman', 'latitude' => 31.9539, 'longitude' => 35.9106],
            ['name_ar' => 'إربد', 'name_en' => 'Irbid', 'latitude' => 32.5556, 'longitude' => 35.8500],
            ['name_ar' => 'الزرقاء', 'name_en' => 'Zarqa', 'latitude' => 32.0728, 'longitude' => 36.0870],
            ['name_ar' => 'العقبة', 'name_en' => 'Aqaba', 'latitude' => 29.5321, 'longitude' => 35.0063],
        ];

        foreach ($cities as $city) {
            $jordan->cities()->updateOrCreate(
                ['name_ar' => $city['name_ar']],
                [...$city, 'is_active' => true],
            );
        }
    }
}
