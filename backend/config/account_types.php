<?php

return [
    'default' => 'regular_user',

    'types' => [
        'regular_user' => [
            'label_ar' => 'مستخدم عادي',
            'requires_verification' => false,
        ],
        'chalet_owner' => [
            'label_ar' => 'صاحب شاليه',
            'requires_verification' => true,
        ],
        'sports_field_owner' => [
            'label_ar' => 'صاحب ملعب',
            'requires_verification' => true,
        ],
        'wedding_hall_owner' => [
            'label_ar' => 'صاحب صالة أفراح',
            'requires_verification' => true,
        ],
        'hotel_owner' => [
            'label_ar' => 'صاحب فندق',
            'requires_verification' => true,
        ],
        'tourism_office_owner' => [
            'label_ar' => 'صاحب مكتب سياحي',
            'requires_verification' => true,
        ],
        'transport_company_owner' => [
            'label_ar' => 'صاحب شركة نقل',
            'requires_verification' => true,
        ],
        'commercial_property_owner' => [
            'label_ar' => 'صاحب محل أو مكتب تجاري',
            'requires_verification' => true,
        ],
        'service_provider' => [
            'label_ar' => 'صاحب خدمة',
            'requires_verification' => false,
        ],
        'technician' => [
            'label_ar' => 'فني / عامل صيانة',
            'requires_verification' => false,
        ],
        'nursery_owner' => [
            'label_ar' => 'صاحب مشتل',
            'requires_verification' => true,
        ],
        'turkish_bath_owner' => [
            'label_ar' => 'صاحب حمام تركي',
            'requires_verification' => true,
        ],
        'amusement_city_owner' => [
            'label_ar' => 'صاحب مدينة ألعاب',
            'requires_verification' => true,
        ],
        'travel_agency_owner' => [
            'label_ar' => 'صاحب مكتب سفريات',
            'requires_verification' => true,
        ],
        'airline_company_owner' => [
            'label_ar' => 'صاحب شركة طيران',
            'requires_verification' => true,
        ],
        'parcel_service_owner' => [
            'label_ar' => 'صاحب خدمة إرسال طرود',
            'requires_verification' => true,
        ],
    ],
];
