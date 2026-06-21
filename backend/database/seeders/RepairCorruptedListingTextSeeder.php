<?php

namespace Database\Seeders;

use App\Models\Listing;
use Illuminate\Database\Seeder;

class RepairCorruptedListingTextSeeder extends Seeder
{
    public function run(): void
    {
        $fallbacks = [
            18 => [
                'title_ar' => 'إعلان شاليه عبر API',
                'description_ar' => 'إعلان تجريبي تم إنشاؤه من واجهة API.',
                'area_name_ar' => null,
            ],
            20 => [
                'title_ar' => 'شاليه مناسب للعائلات',
                'description_ar' => 'شاليه متاح للحجز مع إمكانية التواصل عبر الهاتف أو واتساب.',
                'area_name_ar' => 'عمّان',
            ],
            22 => [
                'title_ar' => 'شاليه للإيجار اليومي',
                'description_ar' => 'إعلان تجريبي لحجز شاليه داخل التطبيق.',
                'area_name_ar' => 'عمّان',
            ],
            23 => [
                'title_ar' => 'شاليه عائلي بإطلالة جميلة',
                'description_ar' => 'مساحة مناسبة للعائلات مع مرافق مريحة وسعر واضح.',
                'area_name_ar' => 'عمّان',
            ],
            24 => [
                'title_ar' => 'شاليه فاخر مع مسبح',
                'description_ar' => 'شاليه مجهز للحجوزات اليومية مع جلسات خارجية ومسبح.',
                'area_name_ar' => 'عمّان',
            ],
        ];

        foreach ($fallbacks as $id => $payload) {
            $listing = Listing::query()->find($id);

            if (! $listing || ! $this->hasCorruptedArabic($listing)) {
                continue;
            }

            $listing->forceFill($payload)->save();
        }
    }

    private function hasCorruptedArabic(Listing $listing): bool
    {
        return str_contains((string) $listing->title_ar, '?')
            || str_contains((string) $listing->description_ar, '?')
            || str_contains((string) $listing->area_name_ar, '?');
    }
}
