<?php

namespace Database\Seeders;

use App\Models\AppBanner;
use App\Models\Category;
use App\Models\Country;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class MarketplaceDemoSeeder extends Seeder
{
    public function run(): void
    {
        $country = Country::query()->updateOrCreate(
            ['iso_code' => 'JO'],
            [
                'name_ar' => 'الأردن',
                'name_en' => 'Jordan',
                'phone_code' => '+962',
                'is_active' => true,
            ],
        );

        $cities = collect($this->cities())->mapWithKeys(function (array $data) use ($country): array {
            $city = $country->cities()->updateOrCreate(
                ['name_ar' => $data['name_ar']],
                [...$data, 'is_active' => true],
            );

            return [$data['name_en'] => $city];
        });

        $owner = User::query()->withTrashed()->firstOrNew([
            'email' => 'demo.owner@iaioi.com',
        ]);
        $owner->fill([
            'name' => 'مالك إعلانات تجريبي',
            'email_verified_at' => $owner->email_verified_at ?? now(),
            'account_type' => 'regular_user',
            'verification_status' => 'none',
            'role' => 'user',
            'status' => 'active',
        ]);

        if (! $owner->exists) {
            $owner->password = Str::random(64);
        }

        $owner->save();

        if ($owner->trashed()) {
            $owner->restore();
        }

        $categories = collect($this->categories())->mapWithKeys(function (array $data): array {
            $category = Category::query()->withTrashed()->updateOrCreate(
                ['slug' => $data['slug']],
                $data,
            );

            if ($category->trashed()) {
                $category->restore();
            }

            return [$category->slug => $category];
        });

        foreach ($this->listings() as $index => $data) {
            $category = $categories->get($data['category_slug']);
            $city = $cities->get($data['city']);
            $images = $data['images'];
            unset($data['category_slug'], $data['city'], $data['images']);

            $listing = Listing::query()->withTrashed()->updateOrCreate(
                ['slug' => $data['slug']],
                [
                    ...$data,
                    'user_id' => $owner->id,
                    'owner_user_id' => $owner->id,
                    'category_id' => $category->id,
                    'country_id' => $country->id,
                    'city_id' => $city->id,
                    'latitude' => $data['latitude'] ?? $city->latitude,
                    'longitude' => $data['longitude'] ?? $city->longitude,
                    'currency_code' => 'JOD',
                    'listing_type' => 'offer',
                    'status' => 'active',
                    'published_at' => now()->subDays($index),
                ],
            );

            if ($listing->trashed()) {
                $listing->restore();
            }

            foreach ($images as $imageIndex => $image) {
                $listing->images()->updateOrCreate(
                    ['sort_order' => $imageIndex],
                    [
                        'path' => $image,
                        'alt_text_ar' => $listing->title_ar,
                        'alt_text_en' => $listing->title_en,
                        'is_cover' => $imageIndex === 0,
                    ],
                );
            }

            $listing->images()->where('sort_order', '>=', count($images))->delete();
            $listing->features()->updateOrCreate(
                ['name_en' => 'Demo listing'],
                [
                    'name_ar' => 'إعلان موثوق',
                    'value_ar' => 'متاح للتواصل والحجز',
                    'value_en' => 'Available for contact and booking',
                    'sort_order' => 1,
                ],
            );
        }

        foreach ($this->banners() as $banner) {
            AppBanner::query()->updateOrCreate(
                [
                    'placement' => $banner['placement'],
                    'sort_order' => $banner['sort_order'],
                ],
                $banner,
            );
        }
    }

    private function cities(): array
    {
        return [
            ['name_ar' => 'عمّان', 'name_en' => 'Amman', 'latitude' => 31.9539, 'longitude' => 35.9106],
            ['name_ar' => 'إربد', 'name_en' => 'Irbid', 'latitude' => 32.5556, 'longitude' => 35.8500],
            ['name_ar' => 'الزرقاء', 'name_en' => 'Zarqa', 'latitude' => 32.0728, 'longitude' => 36.0870],
            ['name_ar' => 'العقبة', 'name_en' => 'Aqaba', 'latitude' => 29.5321, 'longitude' => 35.0063],
        ];
    }

    private function categories(): array
    {
        return [
            ['slug' => 'chalets', 'name_ar' => 'شاليهات', 'name_en' => 'Chalets', 'group_key' => 'bookings', 'icon' => 'home', 'supports_booking' => true, 'sort_order' => 1, 'is_active' => true],
            ['slug' => 'farms', 'name_ar' => 'مزارع', 'name_en' => 'Farms', 'group_key' => 'bookings', 'icon' => 'trees', 'supports_booking' => true, 'sort_order' => 2, 'is_active' => true],
            ['slug' => 'wedding-halls', 'name_ar' => 'قاعات أفراح', 'name_en' => 'Wedding Halls', 'group_key' => 'bookings', 'icon' => 'party-popper', 'supports_booking' => true, 'sort_order' => 3, 'is_active' => true],
            ['slug' => 'rest-areas', 'name_ar' => 'استراحات', 'name_en' => 'Rest Areas', 'group_key' => 'bookings', 'icon' => 'coffee', 'supports_booking' => true, 'sort_order' => 4, 'is_active' => true],
            ['slug' => 'camps', 'name_ar' => 'مخيمات', 'name_en' => 'Camps', 'group_key' => 'entertainment-tourism', 'icon' => 'tent', 'supports_booking' => true, 'sort_order' => 5, 'is_active' => true],
            ['slug' => 'services', 'name_ar' => 'خدمات', 'name_en' => 'Services', 'group_key' => 'services', 'icon' => 'wrench', 'supports_booking' => false, 'sort_order' => 6, 'is_active' => true],
        ];
    }

    private function listings(): array
    {
        return [
            $this->listing('chalets', 'demo-iaioi-dead-sea-chalet', 'شاليه عائلي قرب البحر الميت', 'Family Chalet near the Dead Sea', 'Amman', 'طريق البحر الميت', 120, 'night', 'dead-sea-chalet', true),
            $this->listing('farms', 'demo-iaioi-irbid-farm', 'مزرعة خضراء للمناسبات في إربد', 'Green Event Farm in Irbid', 'Irbid', 'الحصن', 180, 'day', 'irbid-farm'),
            $this->listing('wedding-halls', 'demo-iaioi-zarqa-wedding-hall', 'قاعة أفراح رويال في الزرقاء', 'Royal Wedding Hall in Zarqa', 'Zarqa', 'الزرقاء الجديدة', 850, 'day', 'zarqa-hall', true),
            $this->listing('rest-areas', 'demo-iaioi-amman-rest-area', 'استراحة هادئة للعائلات في عمّان', 'Family Rest Area in Amman', 'Amman', 'ناعور', 75, 'day', 'amman-rest-area'),
            $this->listing('camps', 'demo-iaioi-wadi-rum-camp', 'مخيم وادي رم الصحراوي', 'Wadi Rum Desert Camp', 'Aqaba', 'وادي رم', 55, 'person', 'wadi-rum-camp', true),
            $this->listing('services', 'demo-iaioi-home-services', 'خدمات صيانة منزلية متكاملة', 'Complete Home Maintenance Services', 'Amman', 'جميع مناطق عمّان', 20, 'hour', 'home-services'),
        ];
    }

    private function listing(
        string $categorySlug,
        string $slug,
        string $titleAr,
        string $titleEn,
        string $city,
        string $area,
        int $price,
        string $priceUnit,
        string $imageSeed,
        bool $featured = false,
    ): array {
        return [
            'category_slug' => $categorySlug,
            'slug' => $slug,
            'title_ar' => $titleAr,
            'title_en' => $titleEn,
            'description_ar' => 'إعلان تجريبي متاح الآن مع معلومات واضحة وإمكانية التواصل والحجز.',
            'description_en' => 'A demo listing available now with clear details and contact information.',
            'city' => $city,
            'area_name_ar' => $area,
            'area_name_en' => $area,
            'address_ar' => $area,
            'phone' => '+962790000000',
            'whatsapp' => '+962790000000',
            'base_price' => $price,
            'price_unit' => $priceUnit,
            'is_featured' => $featured,
            'views_count' => 10,
            'images' => [
                "https://picsum.photos/seed/iaioi-{$imageSeed}-1/1200/800",
                "https://picsum.photos/seed/iaioi-{$imageSeed}-2/1200/800",
            ],
        ];
    }

    private function banners(): array
    {
        return [
            [
                'title_ar' => 'اكتشف أفضل الأماكن للحجز',
                'title_en' => 'Discover places to book',
                'subtitle_ar' => 'شاليهات ومزارع وقاعات في مختلف المدن',
                'subtitle_en' => 'Chalets, farms and halls across Jordan',
                'image_url' => 'https://picsum.photos/seed/iaioi-home-banner-1/1600/700',
                'link_url' => '/#/categories',
                'placement' => 'home',
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'title_ar' => 'إعلانات جديدة يومياً',
                'title_en' => 'New listings every day',
                'subtitle_ar' => 'تصفح أحدث الإعلانات والخدمات',
                'subtitle_en' => 'Browse the latest listings and services',
                'image_url' => 'https://picsum.photos/seed/iaioi-home-banner-2/1600/700',
                'link_url' => '/#/all-listings',
                'placement' => 'home',
                'sort_order' => 2,
                'is_active' => true,
            ],
        ];
    }
}
