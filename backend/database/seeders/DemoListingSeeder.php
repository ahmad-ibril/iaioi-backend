<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\CategoryFilter;
use App\Models\City;
use App\Models\Country;
use App\Models\Listing;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DemoListingSeeder extends Seeder
{
    public function run(): void
    {
        $country = Country::query()->where('iso_code', 'JO')->first();
        $cities = City::query()->get()->keyBy('name_en');

        if (! $country) {
            return;
        }

        foreach ($this->listings() as $data) {
            $category = Category::query()->where('slug', $data['category_slug'])->first();

            if (! $category) {
                continue;
            }

            $city = $cities->get($data['city'] ?? 'Amman') ?? $cities->first();

            $listing = Listing::query()
                ->withTrashed()
                ->updateOrCreate(
                    ['slug' => $data['slug']],
                    [
                        'category_id' => $category->id,
                        'country_id' => $country->id,
                        'city_id' => $city?->id,
                        'title_ar' => $data['title_ar'],
                        'title_en' => $data['title_en'] ?? null,
                        'description_ar' => $data['description_ar'] ?? null,
                        'description_en' => $data['description_en'] ?? null,
                        'latitude' => $data['latitude'] ?? $city?->latitude,
                        'longitude' => $data['longitude'] ?? $city?->longitude,
                        'area_name_ar' => $data['area_name_ar'] ?? null,
                        'area_name_en' => $data['area_name_en'] ?? null,
                        'address_ar' => $data['address_ar'] ?? null,
                        'address_en' => $data['address_en'] ?? null,
                        'phone' => $data['phone'] ?? '+962790000000',
                        'whatsapp' => $data['whatsapp'] ?? '+962790000000',
                        'base_price' => $data['base_price'] ?? null,
                        'currency_code' => $data['currency_code'] ?? 'JOD',
                        'price_unit' => $data['price_unit'] ?? 'day',
                        'status' => 'active',
                        'is_featured' => $data['is_featured'] ?? false,
                        'published_at' => Carbon::now()->subDays($data['published_days_ago'] ?? 0),
                    ],
                );

            if ($listing->trashed()) {
                $listing->restore();
            }

            $this->replaceImages($listing, $data['images'] ?? []);
            $this->replaceFeatures($listing, $data['features'] ?? []);
            $this->replaceCalendar($listing, $data['calendar'] ?? []);
            $this->replaceAttributes($listing, $category, $data['attributes'] ?? []);
            $this->syncDetails($listing, $data['details'] ?? []);
            $this->syncHotelRooms($listing, $data['hotel_rooms'] ?? []);
        }
    }

    private function replaceImages(Listing $listing, array $images): void
    {
        $listing->images()->delete();

        foreach (array_values($images) as $index => $path) {
            $listing->images()->create([
                'path' => $path,
                'alt_text_ar' => $listing->title_ar,
                'sort_order' => $index,
                'is_cover' => $index === 0,
            ]);
        }
    }

    private function replaceFeatures(Listing $listing, array $features): void
    {
        $listing->features()->delete();

        foreach (array_values($features) as $index => $feature) {
            $listing->features()->create([
                'name_ar' => $feature[0],
                'value_ar' => $feature[1] ?? null,
                'sort_order' => $index,
            ]);
        }
    }

    private function replaceCalendar(Listing $listing, array $calendar): void
    {
        $listing->calendarDates()->delete();

        foreach ($calendar as $row) {
            $listing->calendarDates()->create([
                'date' => Carbon::today()->addDays($row['offset'])->format('Y-m-d'),
                'status' => $row['status'],
                'price_override' => $row['price_override'] ?? null,
                'note' => $row['note'] ?? null,
            ]);
        }
    }

    private function replaceAttributes(Listing $listing, Category $category, array $attributes): void
    {
        $listing->attributes()->delete();

        $filters = CategoryFilter::query()
            ->where('category_id', $category->id)
            ->get()
            ->keyBy('key');

        foreach ($attributes as $key => $value) {
            $filter = $filters->get($key);

            if (! $filter || $value === null || $value === '') {
                continue;
            }

            $listing->attributes()->create(array_merge(
                [
                    'category_filter_id' => $filter->id,
                    'key' => $key,
                ],
                $this->attributePayload($filter, $value),
            ));
        }
    }

    private function attributePayload(CategoryFilter $filter, mixed $value): array
    {
        $payload = [
            'value_text' => null,
            'value_number' => null,
            'value_boolean' => null,
            'value_date' => null,
            'value_time' => null,
            'value_json' => null,
        ];

        switch ($filter->input_type) {
            case 'number':
            case 'rating':
                $payload['value_number'] = $value;
                break;
            case 'boolean':
                $payload['value_boolean'] = (bool) $value;
                break;
            case 'date':
                $payload['value_date'] = $value;
                break;
            case 'time':
                $payload['value_time'] = $value;
                break;
            case 'multi_select':
                $payload['value_json'] = array_values((array) $value);
                break;
            default:
                $payload['value_text'] = is_array($value)
                    ? json_encode($value, JSON_UNESCAPED_UNICODE)
                    : (string) $value;
        }

        return $payload;
    }

    private function syncDetails(Listing $listing, array $details): void
    {
        $relations = [
            'chalet' => 'chaletDetail',
            'sports_field' => 'sportsFieldDetail',
            'wedding_hall' => 'weddingHallDetail',
            'wedding_supply' => 'weddingSupplyDetail',
            'car_rental' => 'carRentalDetail',
            'bus_rental' => 'busRentalDetail',
            'hotel' => 'hotelDetail',
            'tourism_program' => 'tourismProgramDetail',
        ];

        foreach ($relations as $key => $relation) {
            if (! array_key_exists($key, $details)) {
                continue;
            }

            $listing->{$relation}()->updateOrCreate(
                ['listing_id' => $listing->id],
                $details[$key],
            );
        }
    }

    private function syncHotelRooms(Listing $listing, array $rooms): void
    {
        $listing->hotelRooms()
            ->withTrashed()
            ->get()
            ->each
            ->forceDelete();

        foreach ($rooms as $roomData) {
            $images = $roomData['images'] ?? [];
            $calendar = $roomData['calendar'] ?? [];
            unset($roomData['images'], $roomData['calendar']);

            $room = $listing->hotelRooms()->create($roomData);

            foreach (array_values($images) as $index => $path) {
                $room->images()->create([
                    'path' => $path,
                    'alt_text_ar' => $room->name_ar,
                    'sort_order' => $index,
                    'is_cover' => $index === 0,
                ]);
            }

            foreach ($calendar as $row) {
                $room->calendarDates()->create([
                    'date' => Carbon::today()->addDays($row['offset'])->format('Y-m-d'),
                    'status' => $row['status'],
                    'available_quantity' => $row['available_quantity'] ?? null,
                    'price_override' => $row['price_override'] ?? null,
                ]);
            }
        }
    }

    private function listings(): array
    {
        return [
            [
                'category_slug' => 'chalets',
                'slug' => 'demo-dead-sea-family-chalet',
                'title_ar' => 'شاليه البحر الميت العائلي',
                'title_en' => 'Dead Sea Family Chalet',
                'description_ar' => 'شاليه واسع بإطلالة جميلة، بركة خاصة مدفأة، جلسات خارجية، ومناسب للعائلات.',
                'city' => 'Amman',
                'area_name_ar' => 'طريق البحر الميت',
                'area_name_en' => 'Dead Sea Road',
                'latitude' => 31.7584,
                'longitude' => 35.5901,
                'base_price' => 120,
                'price_unit' => 'night',
                'is_featured' => true,
                'images' => $this->images('chalet', 3),
                'features' => [
                    ['المساحة', '450 متر مربع'],
                    ['الغرف', '4 غرف'],
                    ['البركة', 'مدفأة'],
                    ['الضيوف', 'حتى 18 شخص'],
                ],
                'calendar' => $this->bookingCalendar(),
                'attributes' => [
                    'area_size' => 450,
                    'rooms_count' => 4,
                    'bathrooms_count' => 3,
                    'has_pool' => true,
                    'pool_is_heated' => true,
                    'max_guests' => 18,
                ],
                'details' => [
                    'chalet' => [
                        'area_size' => 450,
                        'rooms_count' => 4,
                        'bathrooms_count' => 3,
                        'max_guests' => 18,
                        'has_pool' => true,
                        'pool_is_heated' => true,
                    ],
                ],
            ],
            [
                'category_slug' => 'chalets',
                'slug' => 'demo-ajloun-mountain-chalet',
                'title_ar' => 'شاليه جبال عجلون',
                'title_en' => 'Ajloun Mountain Chalet',
                'description_ar' => 'شاليه هادئ بين الأشجار مع جلسات شواء ومسبح خارجي وإطلالة جبلية.',
                'city' => 'Irbid',
                'area_name_ar' => 'عجلون',
                'area_name_en' => 'Ajloun',
                'latitude' => 32.3326,
                'longitude' => 35.7517,
                'base_price' => 85,
                'price_unit' => 'night',
                'images' => $this->images('ajloun-chalet', 2),
                'features' => [
                    ['المساحة', '320 متر مربع'],
                    ['الغرف', '3 غرف'],
                    ['البركة', 'غير مدفأة'],
                ],
                'calendar' => $this->bookingCalendar([2, 6], [11]),
                'attributes' => [
                    'area_size' => 320,
                    'rooms_count' => 3,
                    'bathrooms_count' => 2,
                    'has_pool' => true,
                    'pool_is_heated' => false,
                    'max_guests' => 12,
                ],
                'details' => [
                    'chalet' => [
                        'area_size' => 320,
                        'rooms_count' => 3,
                        'bathrooms_count' => 2,
                        'max_guests' => 12,
                        'has_pool' => true,
                        'pool_is_heated' => false,
                    ],
                ],
            ],
            [
                'category_slug' => 'sports-fields',
                'slug' => 'demo-padel-arena-amman',
                'title_ar' => 'ملعب بادل أرينا عمان',
                'title_en' => 'Padel Arena Amman',
                'description_ar' => 'ملعب بادل داخلي بإضاءة ممتازة وحجز بالساعة.',
                'city' => 'Amman',
                'area_name_ar' => 'خلدا',
                'area_name_en' => 'Khalda',
                'latitude' => 31.9953,
                'longitude' => 35.8438,
                'base_price' => 22,
                'price_unit' => 'hour',
                'images' => $this->images('padel', 2),
                'features' => [
                    ['نوع الملعب', 'بادل'],
                    ['الموقع', 'داخلي'],
                    ['الأرضية', 'عشب صناعي'],
                ],
                'calendar' => $this->bookingCalendar([1, 4], [9]),
                'attributes' => [
                    'field_type' => 'padel',
                    'is_indoor' => true,
                ],
                'details' => [
                    'sports_field' => [
                        'field_type' => 'padel',
                        'is_indoor' => true,
                        'surface_type' => 'عشب صناعي',
                        'capacity' => 4,
                    ],
                ],
            ],
            [
                'category_slug' => 'wedding-halls',
                'slug' => 'demo-royal-wedding-hall',
                'title_ar' => 'قاعة رويال للأفراح',
                'title_en' => 'Royal Wedding Hall',
                'description_ar' => 'قاعة أفراح بسعة كبيرة مع ضيافة ومواقف سيارات وتجهيزات صوت وإضاءة.',
                'city' => 'Zarqa',
                'area_name_ar' => 'الزرقاء الجديدة',
                'area_name_en' => 'New Zarqa',
                'latitude' => 32.0608,
                'longitude' => 36.0879,
                'base_price' => 900,
                'price_unit' => 'day',
                'is_featured' => true,
                'images' => $this->images('wedding-hall', 3),
                'features' => [
                    ['السعة', '450 شخص'],
                    ['الضيافة', 'متوفرة'],
                    ['المواقف', '80 موقف'],
                ],
                'calendar' => $this->bookingCalendar([5, 12], [18]),
                'attributes' => [
                    'capacity' => 450,
                    'has_parking' => true,
                    'has_catering' => true,
                ],
                'details' => [
                    'wedding_hall' => [
                        'capacity' => 450,
                        'hall_type' => 'قاعة مغلقة',
                        'has_parking' => true,
                        'has_catering' => true,
                    ],
                ],
            ],
            [
                'category_slug' => 'wedding-supplies',
                'slug' => 'demo-luxury-wedding-package',
                'title_ar' => 'باقة تجهيز كوشة وإضاءة فاخرة',
                'title_en' => 'Luxury Wedding Stage Package',
                'description_ar' => 'باقة تشمل كوشة، ورد صناعي، إضاءة، ممر عروس، وتنسيق كامل للمكان.',
                'city' => 'Amman',
                'area_name_ar' => 'البيادر',
                'area_name_en' => 'Bayader',
                'base_price' => 350,
                'price_unit' => 'product',
                'images' => $this->images('wedding-package', 2),
                'features' => [
                    ['نوع العرض', 'باقة'],
                    ['التركيب', 'مشمول'],
                    ['مدة التجهيز', '4 ساعات'],
                ],
                'attributes' => [
                    'supply_type' => 'package',
                ],
                'details' => [
                    'wedding_supply' => [
                        'supply_type' => 'package',
                        'quantity_available' => 5,
                        'package_items' => ['كوشة', 'إضاءة', 'ورد', 'ممر عروس'],
                    ],
                ],
            ],
            [
                'category_slug' => 'cars',
                'slug' => 'demo-toyota-land-cruiser-rental',
                'title_ar' => 'تأجير تويوتا لاندكروزر',
                'title_en' => 'Toyota Land Cruiser Rental',
                'description_ar' => 'سيارة عائلية مريحة متاحة مع سائق أو بدون سائق حسب الطلب.',
                'city' => 'Amman',
                'area_name_ar' => 'الصويفية',
                'area_name_en' => 'Sweifieh',
                'base_price' => 65,
                'price_unit' => 'day',
                'images' => $this->images('land-cruiser', 2),
                'features' => [
                    ['النوع', 'SUV'],
                    ['المقاعد', '7 ركاب'],
                    ['ناقل الحركة', 'أوتوماتيك'],
                ],
                'calendar' => $this->bookingCalendar([3, 8], [15]),
                'attributes' => [
                    'car_type' => 'SUV',
                    'with_driver' => false,
                    'seats_count' => 7,
                    'transmission' => 'automatic',
                ],
                'details' => [
                    'car_rental' => [
                        'car_type' => 'SUV',
                        'brand' => 'Toyota',
                        'model' => 'Land Cruiser',
                        'year' => 2024,
                        'seats_count' => 7,
                        'with_driver' => false,
                        'transmission' => 'automatic',
                    ],
                ],
            ],
            [
                'category_slug' => 'buses',
                'slug' => 'demo-30-seat-tour-bus',
                'title_ar' => 'حافلة سياحية 30 مقعد',
                'title_en' => '30 Seat Tour Bus',
                'description_ar' => 'حافلة حديثة مع سائق وتكييف مناسبة للرحلات الداخلية والمناسبات.',
                'city' => 'Amman',
                'area_name_ar' => 'ماركا',
                'area_name_en' => 'Marka',
                'base_price' => 180,
                'price_unit' => 'day',
                'images' => $this->images('tour-bus', 2),
                'features' => [
                    ['المقاعد', '30 مقعد'],
                    ['السائق', 'مشمول'],
                    ['التكييف', 'متوفر'],
                ],
                'calendar' => $this->bookingCalendar([4, 10], [17]),
                'attributes' => [
                    'seats_count' => 30,
                    'with_driver' => true,
                    'has_ac' => true,
                ],
                'details' => [
                    'bus_rental' => [
                        'seats_count' => 30,
                        'bus_type' => 'حافلة سياحية',
                        'with_driver' => true,
                        'has_ac' => true,
                    ],
                ],
            ],
            [
                'category_slug' => 'hotels',
                'slug' => 'demo-aqaba-sea-view-hotel',
                'title_ar' => 'فندق العقبة بإطلالة بحرية',
                'title_en' => 'Aqaba Sea View Hotel',
                'description_ar' => 'فندق خمس نجوم قريب من البحر مع غرف عائلية وخدمات مسبح ومواقف.',
                'city' => 'Aqaba',
                'area_name_ar' => 'الشاطئ الجنوبي',
                'area_name_en' => 'South Beach',
                'latitude' => 29.4459,
                'longitude' => 34.9732,
                'base_price' => 95,
                'price_unit' => 'night',
                'is_featured' => true,
                'images' => $this->images('aqaba-hotel', 3),
                'features' => [
                    ['النجوم', '5'],
                    ['الخدمات', 'مسبح، إفطار، مواقف، إنترنت'],
                    ['وقت الدخول', '03:00 مساءً'],
                ],
                'calendar' => $this->bookingCalendar([2, 14], [20]),
                'attributes' => [
                    'stars' => 5,
                    'services' => ['wifi', 'parking', 'pool', 'breakfast'],
                ],
                'details' => [
                    'hotel' => [
                        'stars' => 5,
                        'check_in_time' => '15:00',
                        'check_out_time' => '12:00',
                        'services' => ['wifi', 'parking', 'pool', 'breakfast'],
                    ],
                ],
                'hotel_rooms' => [
                    [
                        'name_ar' => 'غرفة مزدوجة بإطلالة بحرية',
                        'name_en' => 'Sea View Double Room',
                        'room_type' => 'double',
                        'description_ar' => 'غرفة لشخصين مع شرفة مطلة على البحر.',
                        'capacity_adults' => 2,
                        'capacity_children' => 1,
                        'price_per_night' => 95,
                        'currency_code' => 'JOD',
                        'total_rooms' => 8,
                        'is_active' => true,
                        'images' => $this->images('hotel-room-sea', 2),
                        'calendar' => $this->roomCalendar(),
                    ],
                    [
                        'name_ar' => 'جناح عائلي',
                        'name_en' => 'Family Suite',
                        'room_type' => 'suite',
                        'description_ar' => 'جناح مناسب للعائلات مع غرفتين وصالة.',
                        'capacity_adults' => 4,
                        'capacity_children' => 2,
                        'price_per_night' => 160,
                        'currency_code' => 'JOD',
                        'total_rooms' => 4,
                        'is_active' => true,
                        'images' => $this->images('hotel-family-suite', 2),
                        'calendar' => $this->roomCalendar([3, 9]),
                    ],
                ],
            ],
            [
                'category_slug' => 'tourism-offices',
                'slug' => 'demo-istanbul-family-tour',
                'title_ar' => 'برنامج إسطنبول العائلي 5 أيام',
                'title_en' => 'Istanbul Family Tour 5 Days',
                'description_ar' => 'برنامج سياحي يشمل الفندق، المواصلات، جولات داخلية، وأوقات طيران متعددة.',
                'city' => 'Amman',
                'area_name_ar' => 'الشميساني',
                'area_name_en' => 'Shmeisani',
                'base_price' => 420,
                'price_unit' => 'person',
                'images' => $this->images('istanbul-tour', 3),
                'features' => [
                    ['البلد', 'تركيا'],
                    ['المدينة', 'إسطنبول'],
                    ['المدة', '5 أيام'],
                ],
                'calendar' => $this->bookingCalendar([7, 16], [24]),
                'attributes' => [
                    'destination_country' => 'تركيا',
                    'destination_city' => 'إسطنبول',
                    'trip_type' => 'international',
                    'duration_days' => 5,
                    'trip_date' => Carbon::today()->addDays(30)->format('Y-m-d'),
                    'included_services' => ['flight', 'hotel', 'transport', 'guide'],
                    'flight_times' => '08:30 صباحاً، 07:45 مساءً',
                ],
                'details' => [
                    'tourism_program' => [
                        'destination_country' => 'تركيا',
                        'destination_city' => 'إسطنبول',
                        'departure_country' => 'الأردن',
                        'departure_city' => 'عمّان',
                        'duration_days' => 5,
                        'trip_date' => Carbon::today()->addDays(30)->format('Y-m-d'),
                        'trip_type' => 'international',
                        'seats_available' => 18,
                        'included_services' => ['flight', 'hotel', 'transport', 'guide'],
                        'flight_times' => ['08:30', '19:45'],
                    ],
                ],
            ],
            [
                'category_slug' => 'apartments-rent',
                'slug' => 'demo-furnished-apartment-abdoun',
                'title_ar' => 'شقة مفروشة في عبدون',
                'title_en' => 'Furnished Apartment in Abdoun',
                'description_ar' => 'شقة مفروشة بالكامل قريبة من الخدمات، مع مصعد وكراج ومطبخ مجهز.',
                'city' => 'Amman',
                'area_name_ar' => 'عبدون',
                'area_name_en' => 'Abdoun',
                'base_price' => 650,
                'price_unit' => 'month',
                'images' => $this->images('abdoun-apartment', 3),
                'features' => [
                    ['الغرف', '3'],
                    ['الحمامات', '2'],
                    ['المساحة', '150 متر مربع'],
                ],
                'attributes' => [
                    'furnished_status' => 'furnished',
                    'rooms_count' => 3,
                    'bathrooms_count' => 2,
                    'area_size' => 150,
                    'floor_number' => 4,
                    'has_elevator' => true,
                    'has_garage' => true,
                ],
            ],
            [
                'category_slug' => 'turkish-baths',
                'slug' => 'demo-turkish-bath-spa',
                'title_ar' => 'حمام تركي وسبا عائلي',
                'title_en' => 'Family Turkish Bath and Spa',
                'description_ar' => 'جلسات حمام تركي وساونا وبخار ومساج ضمن أوقات مخصصة للعائلات.',
                'city' => 'Amman',
                'area_name_ar' => 'الجبيهة',
                'area_name_en' => 'Jubeiha',
                'base_price' => 18,
                'price_unit' => 'person',
                'images' => $this->images('turkish-bath', 2),
                'features' => [
                    ['الفئة', 'عائلات'],
                    ['الخدمات', 'ساونا، بخار، مساج'],
                    ['الدوام', 'يومياً 10 صباحاً - 11 مساءً'],
                ],
                'calendar' => $this->bookingCalendar([1, 5], [13]),
                'attributes' => [
                    'gender_policy' => 'families',
                    'available_services' => ['massage', 'sauna', 'steam', 'scrub'],
                    'working_hours' => 'يومياً 10 صباحاً - 11 مساءً',
                ],
            ],
            [
                'category_slug' => 'amusement-parks',
                'slug' => 'demo-family-fun-park',
                'title_ar' => 'مدينة المرح العائلية',
                'title_en' => 'Family Fun Park',
                'description_ar' => 'مدينة ألعاب خارجية مناسبة للأطفال والعائلات مع مطاعم وجلسات.',
                'city' => 'Amman',
                'area_name_ar' => 'طبربور',
                'area_name_en' => 'Tabarbour',
                'base_price' => 6,
                'price_unit' => 'person',
                'images' => $this->images('fun-park', 3),
                'features' => [
                    ['النوع', 'خارجية'],
                    ['مناسبة', 'للأطفال والعائلات'],
                    ['الدوام', '4 مساءً - 12 ليلاً'],
                ],
                'calendar' => $this->bookingCalendar([6], [12]),
                'attributes' => [
                    'park_type' => 'outdoor',
                    'suitable_for' => 'both',
                    'working_hours' => '4 مساءً - 12 ليلاً',
                    'available_days' => ['thursday', 'friday', 'saturday'],
                ],
            ],
            [
                'category_slug' => 'electricians',
                'slug' => 'demo-electrician-24-7-amman',
                'title_ar' => 'كهربائي منازل 24 ساعة',
                'title_en' => '24/7 Home Electrician',
                'description_ar' => 'صيانة أعطال الكهرباء، تمديدات، قواطع، إنارة، وزيارات طارئة.',
                'city' => 'Amman',
                'area_name_ar' => 'مرج الحمام',
                'area_name_en' => 'Marj Al Hamam',
                'base_price' => 15,
                'price_unit' => 'hour',
                'images' => $this->images('electrician', 2),
                'features' => [
                    ['التوفر', 'متاح الآن'],
                    ['التقييم', '4.8'],
                    ['نوع الخدمة', 'صيانة وتمديدات'],
                ],
                'calendar' => $this->bookingCalendar([2], [10]),
                'attributes' => [
                    'service_type' => 'صيانة كهرباء',
                    'approximate_price' => 15,
                    'availability_mode' => 'now',
                    'provider_rating' => 4.8,
                ],
            ],
            [
                'category_slug' => 'furniture-moving',
                'slug' => 'demo-furniture-moving-amman',
                'title_ar' => 'شركة نقل أثاث مع فك وتركيب',
                'title_en' => 'Furniture Moving with Assembly',
                'description_ar' => 'نقل أثاث داخل المدينة وبين المحافظات مع عمال تغليف وفك وتركيب.',
                'city' => 'Amman',
                'area_name_ar' => 'القويسمة',
                'area_name_en' => 'Qweismeh',
                'base_price' => 75,
                'price_unit' => 'trip',
                'images' => $this->images('furniture-moving', 2),
                'features' => [
                    ['العمال', '4 عمال'],
                    ['المركبة', 'شاحنة مغلقة'],
                    ['الخدمة', 'فك وتركيب'],
                ],
                'calendar' => $this->bookingCalendar([1, 3], [8]),
                'attributes' => [
                    'vehicle_type' => 'شاحنة مغلقة',
                    'workers_count' => 4,
                    'movement_scope' => 'inside_city',
                    'assembly_service' => true,
                ],
            ],
            [
                'category_slug' => 'nurseries',
                'slug' => 'demo-green-nursery-irbid',
                'title_ar' => 'مشتل الأخضر للنباتات',
                'title_en' => 'Green Nursery',
                'description_ar' => 'بيع أشجار زينة ونباتات داخلية وخارجية مع خدمة توصيل وزراعة.',
                'city' => 'Irbid',
                'area_name_ar' => 'الحصن',
                'area_name_en' => 'Al Husn',
                'base_price' => 4,
                'price_unit' => 'product',
                'images' => $this->images('nursery', 3),
                'features' => [
                    ['الأنواع', 'أشجار زينة ونباتات داخلية'],
                    ['التوصيل', 'متوفر'],
                    ['الزراعة', 'متوفرة'],
                ],
                'attributes' => [
                    'plant_tree_type' => 'نباتات داخلية وأشجار زينة',
                    'delivery_service' => true,
                    'planting_service' => true,
                    'maintenance_service' => true,
                ],
            ],
            [
                'category_slug' => 'international-parcel-offices',
                'slug' => 'demo-fast-parcel-office',
                'title_ar' => 'مكتب إرسال طرود للخارج',
                'title_en' => 'Fast International Parcel Office',
                'description_ar' => 'إرسال طرود ووثائق إلى دول الخليج وأوروبا مع تتبع للشحنة.',
                'city' => 'Amman',
                'area_name_ar' => 'وسط البلد',
                'area_name_en' => 'Downtown',
                'base_price' => 12,
                'price_unit' => 'product',
                'images' => $this->images('parcel-office', 2),
                'features' => [
                    ['الشحن', 'DHL / Aramex'],
                    ['التتبع', 'متوفر'],
                    ['مدة التوصيل', '3 - 7 أيام'],
                ],
                'attributes' => [
                    'destination_country' => 'السعودية',
                    'weight_kg' => 2,
                    'delivery_days' => 4,
                    'shipping_company' => 'Aramex',
                    'has_tracking' => true,
                ],
            ],
        ];
    }

    private function bookingCalendar(array $bookedOffsets = [3, 7], array $blockedOffsets = [12]): array
    {
        $rows = [];

        for ($offset = 0; $offset <= 20; $offset++) {
            $status = 'available';

            if (in_array($offset, $bookedOffsets, true)) {
                $status = 'booked';
            }

            if (in_array($offset, $blockedOffsets, true)) {
                $status = 'blocked';
            }

            $rows[] = [
                'offset' => $offset,
                'status' => $status,
            ];
        }

        return $rows;
    }

    private function roomCalendar(array $bookedOffsets = [4, 12]): array
    {
        return array_map(
            fn (array $row): array => [
                ...$row,
                'available_quantity' => $row['status'] === 'available' ? 3 : 0,
            ],
            $this->bookingCalendar($bookedOffsets, [18]),
        );
    }

    private function images(string $seed, int $count): array
    {
        return array_map(
            fn (int $index): string => "https://picsum.photos/seed/arab-rentals-{$seed}-{$index}/900/600",
            range(1, $count),
        );
    }
}
