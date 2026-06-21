<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->categories() as $index => $categoryData) {
            $filters = $categoryData['filters'] ?? [];
            unset($categoryData['filters']);

            $category = Category::query()->updateOrCreate(
                ['slug' => $categoryData['slug']],
                [
                    ...$categoryData,
                    'sort_order' => $index + 1,
                    'is_active' => true,
                ],
            );

            foreach ($filters as $filterIndex => $filter) {
                $category->filters()->updateOrCreate(
                    ['key' => $filter['key']],
                    [
                        ...$filter,
                        'sort_order' => $filter['sort_order'] ?? $filterIndex + 1,
                        'is_filterable' => $filter['is_filterable'] ?? true,
                    ],
                );
            }
        }
    }

    private function categories(): array
    {
        return [
            ['group_key' => 'bookings', 'name_ar' => 'الشاليهات', 'name_en' => 'Chalets', 'slug' => 'chalets', 'icon' => 'home', 'supports_booking' => true, 'filters' => [
                $this->number('area_size', 'المساحة', 'Area', 'متر مربع', 'sqm'),
                $this->number('rooms_count', 'عدد الغرف', 'Rooms'),
                $this->number('bathrooms_count', 'عدد الحمامات', 'Bathrooms'),
                $this->boolean('has_pool', 'وجود بركة', 'Has pool'),
                $this->boolean('pool_is_heated', 'البركة مدفأة', 'Heated pool'),
                $this->number('max_guests', 'عدد الضيوف', 'Guests'),
            ]],
            ['group_key' => 'bookings', 'name_ar' => 'الملاعب', 'name_en' => 'Sports Fields', 'slug' => 'sports-fields', 'icon' => 'goal', 'supports_booking' => true, 'filters' => [
                $this->select('field_type', 'نوع الملعب', 'Field type', [
                    ['football', 'كرة قدم', 'Football'],
                    ['padel', 'بادل', 'Padel'],
                    ['basketball', 'سلة', 'Basketball'],
                    ['tennis', 'تنس', 'Tennis'],
                    ['other', 'أخرى', 'Other'],
                ]),
                $this->boolean('is_indoor', 'داخلي', 'Indoor'),
            ]],
            ['group_key' => 'bookings', 'name_ar' => 'صالات الأفراح', 'name_en' => 'Wedding Halls', 'slug' => 'wedding-halls', 'icon' => 'party-popper', 'supports_booking' => true, 'filters' => [
                $this->number('capacity', 'السعة', 'Capacity', 'شخص', 'people'),
                $this->boolean('has_parking', 'يوجد مواقف', 'Has parking'),
                $this->boolean('has_catering', 'يوجد ضيافة', 'Has catering'),
            ]],
            ['group_key' => 'bookings', 'name_ar' => 'مستلزمات الأفراح', 'name_en' => 'Wedding Supplies', 'slug' => 'wedding-supplies', 'icon' => 'package', 'supports_booking' => false, 'filters' => [
                $this->select('supply_type', 'نوع العرض', 'Supply type', [
                    ['product', 'منتج', 'Product'],
                    ['package', 'باقة', 'Package'],
                    ['service', 'خدمة', 'Service'],
                    ['other', 'أخرى', 'Other'],
                ]),
            ]],
            ['group_key' => 'bookings', 'name_ar' => 'تأجير السيارات', 'name_en' => 'Car Rentals', 'slug' => 'cars', 'icon' => 'car', 'supports_booking' => true, 'filters' => [
                $this->text('car_type', 'نوع السيارة', 'Car type'),
                $this->boolean('with_driver', 'مع سائق', 'With driver'),
                $this->number('seats_count', 'عدد الركاب', 'Passengers'),
                $this->select('transmission', 'ناقل الحركة', 'Transmission', [
                    ['automatic', 'أوتوماتيك', 'Automatic'],
                    ['manual', 'عادي', 'Manual'],
                ]),
            ]],
            ['group_key' => 'bookings', 'name_ar' => 'تأجير الحافلات', 'name_en' => 'Bus Rentals', 'slug' => 'buses', 'icon' => 'bus', 'supports_booking' => true, 'filters' => [
                $this->number('seats_count', 'عدد المقاعد', 'Seats'),
                $this->boolean('with_driver', 'مع سائق', 'With driver'),
                $this->boolean('has_ac', 'يوجد تكييف', 'Has AC'),
            ]],
            ['group_key' => 'bookings', 'name_ar' => 'الفنادق', 'name_en' => 'Hotels', 'slug' => 'hotels', 'icon' => 'hotel', 'supports_booking' => true, 'filters' => [
                $this->rating('stars', 'عدد النجوم', 'Stars'),
                $this->multiSelect('services', 'الخدمات', 'Services', [
                    ['wifi', 'إنترنت', 'Wi-Fi'],
                    ['parking', 'مواقف', 'Parking'],
                    ['pool', 'مسبح', 'Pool'],
                    ['breakfast', 'إفطار', 'Breakfast'],
                ]),
            ]],
            ['group_key' => 'bookings', 'name_ar' => 'المكاتب السياحية', 'name_en' => 'Tourism Offices', 'slug' => 'tourism-offices', 'icon' => 'map', 'supports_booking' => true, 'filters' => $this->travelFilters()],

            ['group_key' => 'entertainment-tourism', 'name_ar' => 'الحمامات التركية', 'name_en' => 'Turkish Baths', 'slug' => 'turkish-baths', 'icon' => 'waves', 'supports_booking' => true, 'filters' => [
                $this->select('gender_policy', 'للرجال أو النساء أو العائلات', 'Gender policy', [
                    ['men', 'رجال', 'Men'],
                    ['women', 'نساء', 'Women'],
                    ['families', 'عائلات', 'Families'],
                    ['all', 'الجميع', 'All'],
                ]),
                $this->multiSelect('available_services', 'الخدمات المتوفرة', 'Available services', [
                    ['massage', 'مساج', 'Massage'],
                    ['sauna', 'ساونا', 'Sauna'],
                    ['steam', 'بخار', 'Steam'],
                    ['scrub', 'تقشير', 'Scrub'],
                ]),
                $this->text('working_hours', 'الأيام والأوقات المتاحة', 'Available days and times'),
            ]],
            ['group_key' => 'entertainment-tourism', 'name_ar' => 'مدن الألعاب', 'name_en' => 'Amusement Parks', 'slug' => 'amusement-parks', 'icon' => 'ferris-wheel', 'supports_booking' => true, 'filters' => $this->amusementFilters()],
            ['group_key' => 'entertainment-tourism', 'name_ar' => 'مدن الألعاب الداخلية', 'name_en' => 'Indoor Amusement Parks', 'slug' => 'indoor-amusement-parks', 'icon' => 'gamepad-2', 'supports_booking' => true, 'filters' => $this->amusementFilters()],
            ['group_key' => 'entertainment-tourism', 'name_ar' => 'مدن الألعاب المائية', 'name_en' => 'Water Parks', 'slug' => 'water-parks', 'icon' => 'droplets', 'supports_booking' => true, 'filters' => $this->amusementFilters()],
            ['group_key' => 'entertainment-tourism', 'name_ar' => 'الحدائق بأنواعها', 'name_en' => 'Parks', 'slug' => 'parks', 'icon' => 'trees', 'supports_booking' => false, 'filters' => [
                $this->select('park_type', 'نوع الحديقة', 'Park type', [
                    ['public', 'عامة', 'Public'],
                    ['family', 'عائلية', 'Family'],
                    ['natural', 'طبيعية', 'Natural'],
                    ['kids', 'أطفال', 'Kids'],
                ]),
                $this->boolean('family_friendly', 'مناسبة للعائلات', 'Family friendly'),
                $this->boolean('has_kids_games', 'يوجد ألعاب أطفال', 'Has kids games'),
                $this->boolean('has_seating', 'يوجد جلسات', 'Has seating'),
                $this->boolean('has_restaurants_cafes', 'يوجد مطاعم أو كافيهات', 'Has restaurants or cafes'),
                $this->number('entry_fee', 'رسوم الدخول', 'Entry fee'),
            ]],
            ['group_key' => 'entertainment-tourism', 'name_ar' => 'شركات النقل السياحي', 'name_en' => 'Tourist Transport Companies', 'slug' => 'tourist-transport-companies', 'icon' => 'bus-front', 'supports_booking' => true, 'filters' => [
                $this->text('vehicle_type', 'نوع المركبة', 'Vehicle type'),
                $this->number('passengers_count', 'عدد الركاب', 'Passengers'),
                $this->boolean('with_driver', 'مع سائق', 'With driver'),
                $this->select('travel_scope', 'داخلية أو خارجية', 'Travel scope', [
                    ['domestic', 'داخلية', 'Domestic'],
                    ['international', 'خارجية', 'International'],
                ]),
            ]],
            ['group_key' => 'entertainment-tourism', 'name_ar' => 'شركات الطيران', 'name_en' => 'Airlines', 'slug' => 'airlines', 'icon' => 'plane', 'supports_booking' => true, 'filters' => [
                $this->text('destination_country', 'البلد', 'Destination country'),
                $this->select('flight_type', 'نوع الرحلة', 'Flight type', [
                    ['domestic', 'داخلية', 'Domestic'],
                    ['international', 'خارجية', 'International'],
                ]),
                $this->text('flight_times', 'أوقات الطيران المتاحة', 'Flight times'),
            ]],
            ['group_key' => 'entertainment-tourism', 'name_ar' => 'مكاتب السفريات', 'name_en' => 'Travel Agencies', 'slug' => 'travel-agencies', 'icon' => 'briefcase', 'supports_booking' => true, 'filters' => $this->travelFilters()],
            ['group_key' => 'entertainment-tourism', 'name_ar' => 'مكاتب إرسال الطرود للخارج', 'name_en' => 'International Parcel Offices', 'slug' => 'international-parcel-offices', 'icon' => 'package-check', 'supports_booking' => false, 'filters' => [
                $this->text('destination_country', 'الدولة', 'Country'),
                $this->number('weight_kg', 'الوزن', 'Weight', 'كغم', 'kg'),
                $this->number('delivery_days', 'مدة التوصيل', 'Delivery duration', 'يوم', 'days'),
                $this->text('shipping_company', 'شركة الشحن', 'Shipping company'),
                $this->boolean('has_tracking', 'تتبع الشحنة', 'Shipment tracking'),
            ]],

            ['group_key' => 'real-estate', 'name_ar' => 'شقق للإيجار', 'name_en' => 'Apartments for Rent', 'slug' => 'apartments-rent', 'icon' => 'building-2', 'supports_booking' => false, 'filters' => [
                $this->select('furnished_status', 'مفروشة أو غير مفروشة', 'Furnished status', [
                    ['furnished', 'مفروشة', 'Furnished'],
                    ['unfurnished', 'غير مفروشة', 'Unfurnished'],
                ]),
                $this->number('rooms_count', 'عدد الغرف', 'Rooms'),
                $this->number('bathrooms_count', 'عدد الحمامات', 'Bathrooms'),
                $this->number('area_size', 'المساحة', 'Area', 'متر مربع', 'sqm'),
                $this->number('floor_number', 'الطابق', 'Floor'),
                $this->boolean('has_elevator', 'يوجد مصعد', 'Has elevator'),
                $this->boolean('has_garage', 'يوجد كراج', 'Has garage'),
            ]],
            ['group_key' => 'real-estate', 'name_ar' => 'مجمعات تجارية', 'name_en' => 'Commercial Complexes', 'slug' => 'commercial-complexes', 'icon' => 'landmark', 'supports_booking' => false, 'filters' => $this->commercialPropertyFilters()],
            ['group_key' => 'real-estate', 'name_ar' => 'مكاتب تجارية', 'name_en' => 'Commercial Offices', 'slug' => 'commercial-offices', 'icon' => 'briefcase-business', 'supports_booking' => false, 'filters' => $this->commercialPropertyFilters()],
            ['group_key' => 'real-estate', 'name_ar' => 'محلات تجارية', 'name_en' => 'Commercial Shops', 'slug' => 'commercial-shops', 'icon' => 'store', 'supports_booking' => false, 'filters' => $this->commercialPropertyFilters()],

            ['group_key' => 'services', 'name_ar' => 'عمال البناء', 'name_en' => 'Construction Workers', 'slug' => 'construction-workers', 'icon' => 'hard-hat', 'supports_booking' => true, 'filters' => $this->serviceProviderFilters()],
            ['group_key' => 'services', 'name_ar' => 'عمال الصيانة بجميع أنواعها', 'name_en' => 'Maintenance Workers', 'slug' => 'maintenance-workers', 'icon' => 'wrench', 'supports_booking' => true, 'filters' => $this->serviceProviderFilters()],
            ['group_key' => 'services', 'name_ar' => 'كهربائي', 'name_en' => 'Electrician', 'slug' => 'electricians', 'icon' => 'zap', 'supports_booking' => true, 'filters' => $this->serviceProviderFilters()],
            ['group_key' => 'services', 'name_ar' => 'سباك', 'name_en' => 'Plumber', 'slug' => 'plumbers', 'icon' => 'pipe', 'supports_booking' => true, 'filters' => $this->serviceProviderFilters()],
            ['group_key' => 'services', 'name_ar' => 'نجار', 'name_en' => 'Carpenter', 'slug' => 'carpenters', 'icon' => 'hammer', 'supports_booking' => true, 'filters' => $this->serviceProviderFilters()],
            ['group_key' => 'services', 'name_ar' => 'حداد', 'name_en' => 'Blacksmith', 'slug' => 'blacksmiths', 'icon' => 'anvil', 'supports_booking' => true, 'filters' => $this->serviceProviderFilters()],
            ['group_key' => 'services', 'name_ar' => 'دهان', 'name_en' => 'Painter', 'slug' => 'painters', 'icon' => 'paintbrush', 'supports_booking' => true, 'filters' => $this->serviceProviderFilters()],
            ['group_key' => 'services', 'name_ar' => 'فني تكييف', 'name_en' => 'AC Technician', 'slug' => 'ac-technicians', 'icon' => 'snowflake', 'supports_booking' => true, 'filters' => $this->serviceProviderFilters()],
            ['group_key' => 'services', 'name_ar' => 'فني ألمنيوم', 'name_en' => 'Aluminum Technician', 'slug' => 'aluminum-technicians', 'icon' => 'panel-top', 'supports_booking' => true, 'filters' => $this->serviceProviderFilters()],
            ['group_key' => 'services', 'name_ar' => 'فني بلاط', 'name_en' => 'Tile Technician', 'slug' => 'tile-technicians', 'icon' => 'grid-3x3', 'supports_booking' => true, 'filters' => $this->serviceProviderFilters()],
            ['group_key' => 'services', 'name_ar' => 'عمال تنظيف', 'name_en' => 'Cleaning Workers', 'slug' => 'cleaning-workers', 'icon' => 'sparkles', 'supports_booking' => true, 'filters' => $this->serviceProviderFilters()],
            ['group_key' => 'services', 'name_ar' => 'خدمات الضيافة', 'name_en' => 'Hospitality Services', 'slug' => 'hospitality-services', 'icon' => 'utensils', 'supports_booking' => true, 'filters' => $this->serviceProviderFilters()],
            ['group_key' => 'services', 'name_ar' => 'استقدام العاملات', 'name_en' => 'Domestic Workers Recruitment', 'slug' => 'domestic-workers-recruitment', 'icon' => 'users', 'supports_booking' => false, 'filters' => $this->serviceProviderFilters()],
            ['group_key' => 'services', 'name_ar' => 'خدمات نقل الأثاث', 'name_en' => 'Furniture Moving Services', 'slug' => 'furniture-moving', 'icon' => 'truck', 'supports_booking' => true, 'filters' => [
                $this->text('vehicle_type', 'نوع المركبة', 'Vehicle type'),
                $this->number('workers_count', 'عدد العمال', 'Workers count'),
                $this->select('movement_scope', 'داخل المدينة أو بين المحافظات', 'Movement scope', [
                    ['inside_city', 'داخل المدينة', 'Inside city'],
                    ['between_governorates', 'بين المحافظات', 'Between governorates'],
                ]),
                $this->boolean('assembly_service', 'مع فك وتركيب', 'With disassembly and assembly'),
            ]],
            ['group_key' => 'services', 'name_ar' => 'النقل الخصوصي', 'name_en' => 'Private Transport', 'slug' => 'private-transport', 'icon' => 'car-front', 'supports_booking' => true, 'filters' => [
                $this->text('car_type', 'نوع السيارة', 'Car type'),
                $this->boolean('with_driver', 'مع سائق', 'With driver'),
                $this->number('passengers_count', 'عدد الركاب', 'Passengers'),
                $this->boolean('available_by_appointment', 'متاح حسب الموعد', 'Available by appointment'),
            ]],

            ['group_key' => 'garden-nursery', 'name_ar' => 'خدمات تصميم وتنسيق الحدائق', 'name_en' => 'Garden Design and Landscaping', 'slug' => 'garden-design-landscaping', 'icon' => 'flower-2', 'supports_booking' => true, 'filters' => $this->gardenServiceFilters()],
            ['group_key' => 'garden-nursery', 'name_ar' => 'قص الأشجار', 'name_en' => 'Tree Trimming', 'slug' => 'tree-trimming', 'icon' => 'scissors', 'supports_booking' => true, 'filters' => $this->gardenServiceFilters()],
            ['group_key' => 'garden-nursery', 'name_ar' => 'زراعة العشب', 'name_en' => 'Grass Planting', 'slug' => 'grass-planting', 'icon' => 'sprout', 'supports_booking' => true, 'filters' => $this->gardenServiceFilters()],
            ['group_key' => 'garden-nursery', 'name_ar' => 'أنظمة الري', 'name_en' => 'Irrigation Systems', 'slug' => 'irrigation-systems', 'icon' => 'droplet', 'supports_booking' => true, 'filters' => $this->gardenServiceFilters()],
            ['group_key' => 'garden-nursery', 'name_ar' => 'صيانة الحدائق', 'name_en' => 'Garden Maintenance', 'slug' => 'garden-maintenance', 'icon' => 'leaf', 'supports_booking' => true, 'filters' => $this->gardenServiceFilters()],
            ['group_key' => 'garden-nursery', 'name_ar' => 'المشاتل', 'name_en' => 'Nurseries', 'slug' => 'nurseries', 'icon' => 'warehouse', 'supports_booking' => false, 'filters' => $this->gardenStoreFilters()],
            ['group_key' => 'garden-nursery', 'name_ar' => 'عرض أنواع الأشجار', 'name_en' => 'Tree Types', 'slug' => 'tree-types', 'icon' => 'tree-pine', 'supports_booking' => false, 'filters' => $this->gardenStoreFilters()],
            ['group_key' => 'garden-nursery', 'name_ar' => 'عرض أنواع النباتات', 'name_en' => 'Plant Types', 'slug' => 'plant-types', 'icon' => 'flower', 'supports_booking' => false, 'filters' => $this->gardenStoreFilters()],
            ['group_key' => 'garden-nursery', 'name_ar' => 'بيع مستلزمات الزراعة', 'name_en' => 'Farming Supplies', 'slug' => 'farming-supplies', 'icon' => 'shovel', 'supports_booking' => false, 'filters' => $this->gardenStoreFilters()],
        ];
    }

    private function travelFilters(): array
    {
        return [
            $this->text('destination_country', 'البلد', 'Destination country'),
            $this->text('destination_city', 'المدينة', 'Destination city'),
            $this->select('trip_type', 'داخلية أو خارجية', 'Trip type', [
                ['domestic', 'داخلية', 'Domestic'],
                ['international', 'خارجية', 'International'],
            ]),
            $this->number('duration_days', 'مدة الرحلة', 'Trip duration', 'يوم', 'days'),
            $this->date('trip_date', 'تاريخ الرحلة', 'Trip date'),
            $this->multiSelect('included_services', 'الخدمات المشمولة', 'Included services', [
                ['flight', 'طيران', 'Flight'],
                ['hotel', 'فندق', 'Hotel'],
                ['transport', 'مواصلات', 'Transport'],
                ['guide', 'مرشد سياحي', 'Tour guide'],
            ]),
            $this->text('flight_times', 'أوقات الطيران المتاحة', 'Flight times'),
        ];
    }

    private function amusementFilters(): array
    {
        return [
            $this->select('park_type', 'داخلية أو خارجية أو مائية', 'Park type', [
                ['indoor', 'داخلية', 'Indoor'],
                ['outdoor', 'خارجية', 'Outdoor'],
                ['water', 'مائية', 'Water'],
            ]),
            $this->select('suitable_for', 'مناسبة للأطفال أو العائلات', 'Suitable for', [
                ['children', 'أطفال', 'Children'],
                ['families', 'عائلات', 'Families'],
                ['both', 'الأطفال والعائلات', 'Children and families'],
            ]),
            $this->text('working_hours', 'أوقات الدوام', 'Working hours'),
            $this->multiSelect('available_days', 'الأيام المتاحة', 'Available days', $this->weekDays()),
        ];
    }

    private function commercialPropertyFilters(): array
    {
        return [
            $this->number('area_size', 'المساحة', 'Area', 'متر مربع', 'sqm'),
            $this->select('property_type', 'نوع العقار', 'Property type', [
                ['commercial_complex', 'مجمع تجاري', 'Commercial complex'],
                ['office', 'مكتب تجاري', 'Commercial office'],
                ['shop', 'محل تجاري', 'Commercial shop'],
            ]),
            $this->select('deal_type', 'للبيع أو للإيجار', 'Deal type', [
                ['sale', 'للبيع', 'For sale'],
                ['rent', 'للإيجار', 'For rent'],
            ]),
            $this->text('location_note', 'الموقع', 'Location'),
            $this->number('parking_spaces', 'عدد المواقف', 'Parking spaces'),
        ];
    }

    private function serviceProviderFilters(): array
    {
        return [
            $this->text('service_type', 'نوع الخدمة', 'Service type'),
            $this->number('approximate_price', 'السعر التقريبي', 'Approximate price'),
            $this->select('availability_mode', 'متاح الآن أو حسب موعد', 'Availability', [
                ['now', 'متاح الآن', 'Available now'],
                ['appointment', 'حسب موعد', 'By appointment'],
            ]),
            $this->rating('provider_rating', 'تقييم المزود', 'Provider rating'),
        ];
    }

    private function gardenServiceFilters(): array
    {
        return [
            $this->text('plant_tree_type', 'نوع النبات أو الشجر', 'Plant or tree type'),
            $this->boolean('delivery_service', 'خدمة توصيل', 'Delivery service'),
            $this->boolean('planting_service', 'خدمة زراعة', 'Planting service'),
            $this->boolean('maintenance_service', 'خدمة صيانة', 'Maintenance service'),
        ];
    }

    private function gardenStoreFilters(): array
    {
        return [
            $this->text('plant_tree_type', 'نوع النبات أو الشجر', 'Plant or tree type'),
            $this->boolean('delivery_service', 'خدمة توصيل', 'Delivery service'),
            $this->boolean('planting_service', 'خدمة زراعة', 'Planting service'),
            $this->boolean('maintenance_service', 'خدمة صيانة', 'Maintenance service'),
        ];
    }

    private function weekDays(): array
    {
        return [
            ['saturday', 'السبت', 'Saturday'],
            ['sunday', 'الأحد', 'Sunday'],
            ['monday', 'الاثنين', 'Monday'],
            ['tuesday', 'الثلاثاء', 'Tuesday'],
            ['wednesday', 'الأربعاء', 'Wednesday'],
            ['thursday', 'الخميس', 'Thursday'],
            ['friday', 'الجمعة', 'Friday'],
        ];
    }

    private function text(string $key, string $labelAr, string $labelEn): array
    {
        return $this->filter($key, $labelAr, $labelEn, 'text');
    }

    private function number(string $key, string $labelAr, string $labelEn, ?string $unitAr = null, ?string $unitEn = null): array
    {
        return $this->filter($key, $labelAr, $labelEn, 'number', unitAr: $unitAr, unitEn: $unitEn, isSortable: true);
    }

    private function rating(string $key, string $labelAr, string $labelEn): array
    {
        return $this->filter($key, $labelAr, $labelEn, 'rating', isSortable: true);
    }

    private function boolean(string $key, string $labelAr, string $labelEn): array
    {
        return $this->filter($key, $labelAr, $labelEn, 'boolean');
    }

    private function date(string $key, string $labelAr, string $labelEn): array
    {
        return $this->filter($key, $labelAr, $labelEn, 'date', isSortable: true);
    }

    private function select(string $key, string $labelAr, string $labelEn, array $options): array
    {
        return $this->filter($key, $labelAr, $labelEn, 'select', $options);
    }

    private function multiSelect(string $key, string $labelAr, string $labelEn, array $options): array
    {
        return $this->filter($key, $labelAr, $labelEn, 'multi_select', $options);
    }

    private function filter(
        string $key,
        string $labelAr,
        string $labelEn,
        string $inputType,
        array $options = [],
        ?string $unitAr = null,
        ?string $unitEn = null,
        bool $isSortable = false,
    ): array {
        return [
            'key' => $key,
            'label_ar' => $labelAr,
            'label_en' => $labelEn,
            'input_type' => $inputType,
            'options' => $options === [] ? null : [
                'values' => array_map(
                    fn (array $option): array => [
                        'value' => $option[0],
                        'label_ar' => $option[1],
                        'label_en' => $option[2],
                    ],
                    $options,
                ),
            ],
            'unit_ar' => $unitAr,
            'unit_en' => $unitEn,
            'is_required' => false,
            'is_filterable' => true,
            'is_sortable' => $isSortable,
        ];
    }
}
