import '../../core/config/app_config.dart';
import 'availability_slot_model.dart';
import 'calendar_date_model.dart';
import 'category_model.dart';

class ListingModel {
  const ListingModel({
    required this.id,
    required this.titleAr,
    required this.slug,
    this.titleEn,
    this.descriptionAr,
    this.category,
    this.cityName,
    this.areaName,
    this.address,
    this.phone,
    this.whatsapp,
    this.basePrice,
    this.currencyCode,
    this.priceUnit,
    this.status,
    this.latitude,
    this.longitude,
    this.distanceKm,
    this.isFeatured = false,
    this.hasSpecialOffer = false,
    this.availableToday = false,
    this.viewsCount,
    this.images = const [],
    this.media = const [],
    this.features = const [],
    this.calendarDates = const [],
    this.availabilitySlots = const [],
    this.attributes = const [],
    this.details = const {},
    this.hotelRooms = const [],
  });

  final int id;
  final String titleAr;
  final String? titleEn;
  final String slug;
  final String? descriptionAr;
  final CategoryModel? category;
  final String? cityName;
  final String? areaName;
  final String? address;
  final String? phone;
  final String? whatsapp;
  final String? basePrice;
  final String? currencyCode;
  final String? priceUnit;
  final String? status;
  final double? latitude;
  final double? longitude;
  final double? distanceKm;
  final bool isFeatured;
  final bool hasSpecialOffer;
  final bool availableToday;
  final int? viewsCount;
  final List<ListingImageModel> images;
  final List<ListingMediaModel> media;
  final List<ListingFeatureModel> features;
  final List<CalendarDateModel> calendarDates;
  final List<AvailabilitySlotModel> availabilitySlots;
  final List<ListingAttributeModel> attributes;
  final Map<String, dynamic> details;
  final List<HotelRoomModel> hotelRooms;

  String get locationText => [
    cityName,
    areaName,
  ].whereType<String>().where((item) => item.isNotEmpty).join(' - ');

  List<String> get imageUrls {
    final urls = <String>[];

    for (final item in media) {
      if (item.mediaType == 'image' &&
          item.url.isNotEmpty &&
          !urls.contains(item.url)) {
        urls.add(item.url);
      }
    }

    for (final image in images) {
      if (image.url.isNotEmpty && !urls.contains(image.url)) {
        urls.add(image.url);
      }
    }

    return urls;
  }

  List<String> get videoUrls => media
      .where((item) => item.mediaType == 'video' && item.url.isNotEmpty)
      .map((item) => item.url)
      .toList();

  String? get coverImageUrl {
    for (final item in media) {
      if (item.mediaType == 'image' && item.isCover && item.url.isNotEmpty) {
        return item.url;
      }
    }

    for (final item in images) {
      if (item.isCover && item.url.isNotEmpty) return item.url;
    }

    return imageUrls.isEmpty ? null : imageUrls.first;
  }

  String get priceText {
    if (basePrice == null || basePrice!.isEmpty) return 'السعر عند التواصل';

    final unit = switch (priceUnit) {
      'hour' => '/ ساعة',
      'day' => '/ يوم',
      'night' => '/ ليلة',
      'trip' => '/ رحلة',
      'person' => '/ شخص',
      'month' => '/ شهر',
      'product' => '/ منتج',
      _ => '',
    };

    return '$basePrice ${currencyCode ?? ''} $unit'.trim();
  }

  factory ListingModel.fromJson(Map<String, dynamic> json) {
    return ListingModel(
      id: json['id'] ?? 0,
      titleAr: json['title_ar'] ?? '',
      titleEn: json['title_en'],
      slug: json['slug'] ?? '',
      descriptionAr: json['description_ar'],
      category: json['category'] is Map<String, dynamic>
          ? CategoryModel.fromJson(json['category'])
          : null,
      cityName: json['city'] is Map<String, dynamic>
          ? json['city']['name_ar']
          : null,
      areaName: json['area_name_ar'],
      address: json['address_ar'] ?? json['address'],
      phone: json['phone'],
      whatsapp: json['whatsapp'],
      basePrice: json['base_price']?.toString(),
      currencyCode: json['currency_code'],
      priceUnit: json['price_unit'],
      status: json['status'],
      latitude: double.tryParse('${json['latitude'] ?? ''}'),
      longitude: double.tryParse('${json['longitude'] ?? ''}'),
      distanceKm: double.tryParse('${json['distance_km'] ?? ''}'),
      isFeatured: _truthy(json['is_featured'] ?? json['featured']),
      hasSpecialOffer: _truthy(
        json['has_special_offer'] ?? json['special_offer'] ?? json['has_offer'],
      ),
      availableToday: _truthy(json['available_today']),
      viewsCount: int.tryParse('${json['views_count'] ?? ''}'),
      images: (json['images'] as List? ?? [])
          .whereType<Map<String, dynamic>>()
          .map(ListingImageModel.fromJson)
          .toList(),
      media: (json['media'] as List? ?? [])
          .whereType<Map<String, dynamic>>()
          .map(ListingMediaModel.fromJson)
          .toList(),
      features: (json['features'] as List? ?? [])
          .whereType<Map<String, dynamic>>()
          .map(ListingFeatureModel.fromJson)
          .toList(),
      calendarDates: (json['calendar_dates'] as List? ?? [])
          .whereType<Map<String, dynamic>>()
          .map(CalendarDateModel.fromJson)
          .toList(),
      availabilitySlots: (json['availability_slots'] as List? ?? [])
          .whereType<Map<String, dynamic>>()
          .map(AvailabilitySlotModel.fromJson)
          .toList(),
      attributes: (json['attributes'] as List? ?? [])
          .whereType<Map<String, dynamic>>()
          .map(ListingAttributeModel.fromJson)
          .toList(),
      details: json['details'] is Map<String, dynamic>
          ? Map<String, dynamic>.from(json['details'])
          : const {},
      hotelRooms: (json['hotel_rooms'] as List? ?? [])
          .whereType<Map<String, dynamic>>()
          .map(HotelRoomModel.fromJson)
          .toList(),
    );
  }
}

class ListingImageModel {
  const ListingImageModel({required this.url, this.isCover = false});

  final String url;
  final bool isCover;

  factory ListingImageModel.fromJson(Map<String, dynamic> json) {
    return ListingImageModel(
      url: AppConfig.mediaUrl(json['url'] ?? json['path']),
      isCover: json['is_cover'] ?? false,
    );
  }
}

class ListingMediaModel {
  const ListingMediaModel({
    required this.id,
    required this.url,
    required this.mediaType,
    this.path,
    this.mimeType,
    this.isCover = false,
  });

  final int id;
  final String url;
  final String mediaType;
  final String? path;
  final String? mimeType;
  final bool isCover;

  factory ListingMediaModel.fromJson(Map<String, dynamic> json) {
    return ListingMediaModel(
      id: json['id'] ?? 0,
      url: AppConfig.mediaUrl(json['url'] ?? json['path']),
      mediaType: json['media_type'] ?? 'image',
      path: json['path'],
      mimeType: json['mime_type'],
      isCover: json['is_cover'] ?? false,
    );
  }
}

class ListingFeatureModel {
  const ListingFeatureModel({required this.nameAr, this.valueAr});

  final String nameAr;
  final String? valueAr;

  factory ListingFeatureModel.fromJson(Map<String, dynamic> json) {
    return ListingFeatureModel(
      nameAr: json['name_ar'] ?? '',
      valueAr: json['value_ar'],
    );
  }
}

class ListingAttributeModel {
  const ListingAttributeModel({
    required this.key,
    required this.labelAr,
    this.value,
  });

  final String key;
  final String labelAr;
  final dynamic value;

  String get displayValue => _displayValue(value);

  factory ListingAttributeModel.fromJson(Map<String, dynamic> json) {
    final filter = json['filter'] is Map<String, dynamic>
        ? Map<String, dynamic>.from(json['filter'])
        : const <String, dynamic>{};

    return ListingAttributeModel(
      key: json['key'] ?? '',
      labelAr: filter['label_ar'] ?? json['key'] ?? '',
      value: json['value'],
    );
  }
}

class HotelRoomModel {
  const HotelRoomModel({
    required this.id,
    required this.nameAr,
    this.roomType,
    this.descriptionAr,
    this.capacityAdults,
    this.capacityChildren,
    this.pricePerNight,
    this.currencyCode,
    this.totalRooms,
    this.images = const [],
    this.calendarDates = const [],
  });

  final int id;
  final String nameAr;
  final String? roomType;
  final String? descriptionAr;
  final int? capacityAdults;
  final int? capacityChildren;
  final String? pricePerNight;
  final String? currencyCode;
  final int? totalRooms;
  final List<ListingImageModel> images;
  final List<CalendarDateModel> calendarDates;

  String get priceText => pricePerNight == null
      ? 'السعر عند التواصل'
      : '$pricePerNight ${currencyCode ?? ''} / ليلة'.trim();

  String get capacityText {
    final adults = capacityAdults ?? 0;
    final children = capacityChildren ?? 0;

    if (adults == 0 && children == 0) return '';
    if (children == 0) return '$adults بالغ';

    return '$adults بالغ، $children طفل';
  }

  factory HotelRoomModel.fromJson(Map<String, dynamic> json) {
    return HotelRoomModel(
      id: json['id'] ?? 0,
      nameAr: json['name_ar'] ?? '',
      roomType: json['room_type'],
      descriptionAr: json['description_ar'],
      capacityAdults: int.tryParse('${json['capacity_adults'] ?? ''}'),
      capacityChildren: int.tryParse('${json['capacity_children'] ?? ''}'),
      pricePerNight: json['price_per_night']?.toString(),
      currencyCode: json['currency_code'],
      totalRooms: int.tryParse('${json['total_rooms'] ?? ''}'),
      images: (json['images'] as List? ?? [])
          .whereType<Map<String, dynamic>>()
          .map(ListingImageModel.fromJson)
          .toList(),
      calendarDates: (json['calendar_dates'] as List? ?? [])
          .whereType<Map<String, dynamic>>()
          .map(CalendarDateModel.fromJson)
          .toList(),
    );
  }
}

String _displayValue(dynamic value) {
  if (value == null || value == '') return '';

  if (value is bool) return value ? 'نعم' : 'لا';

  if (value is List) {
    return value.map(_displayValue).where((item) => item.isNotEmpty).join('، ');
  }

  if (value is Map) {
    return value.values
        .map(_displayValue)
        .where((item) => item.isNotEmpty)
        .join('، ');
  }

  return '$value';
}

bool _truthy(dynamic value) {
  if (value is bool) return value;
  if (value is num) return value > 0;
  final text = '$value'.toLowerCase().trim();
  return text == '1' || text == 'true' || text == 'yes';
}
