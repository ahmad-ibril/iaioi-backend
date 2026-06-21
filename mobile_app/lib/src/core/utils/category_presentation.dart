import 'package:flutter/material.dart';

import '../../data/models/category_model.dart';

class CategoryPresentation {
  const CategoryPresentation._();

  static const maintenanceSlug = 'maintenance-services';
  static const gardenServicesSlug = 'garden-services';
  static const nurseryProductsSlug = 'nursery-products';

  static const maintenanceSlugs = <String>{
    'construction-workers',
    'maintenance-workers',
    'electricians',
    'plumbers',
    'carpenters',
    'blacksmiths',
    'painters',
    'ac-technicians',
    'aluminum-technicians',
    'tile-technicians',
    'cleaning-workers',
  };

  static const gardenServiceSlugs = <String>{
    'garden-design-landscaping',
    'tree-trimming',
    'grass-planting',
    'irrigation-systems',
    'garden-maintenance',
  };

  static const nurseryProductSlugs = <String>{
    'nurseries',
    'tree-types',
    'plant-types',
    'farming-supplies',
  };

  static const _virtualMaintenance = CategoryModel(
    id: -10,
    nameAr: 'الصيانة والخدمات',
    slug: maintenanceSlug,
    groupKey: 'services',
    icon: 'build',
    supportsBooking: false,
  );

  static const _virtualGardenServices = CategoryModel(
    id: -11,
    nameAr: 'خدمات الزراعة والحدائق',
    slug: gardenServicesSlug,
    groupKey: 'garden-nursery',
    icon: 'yard',
    supportsBooking: false,
  );

  static const _virtualNurseryProducts = CategoryModel(
    id: -12,
    nameAr: 'المشاتل والمنتجات الزراعية',
    slug: nurseryProductsSlug,
    groupKey: 'garden-nursery',
    icon: 'local_florist',
    supportsBooking: false,
  );

  static List<CategoryModel> groupedCategories(List<CategoryModel> source) {
    var addedMaintenance = false;
    var addedGardenServices = false;
    var addedNurseryProducts = false;
    final grouped = <CategoryModel>[];

    for (final category in source) {
      if (maintenanceSlugs.contains(category.slug)) {
        if (!addedMaintenance) {
          grouped.add(
            _withMergedData(_virtualMaintenance, source, maintenanceSlugs),
          );
          addedMaintenance = true;
        }
        continue;
      }

      if (gardenServiceSlugs.contains(category.slug)) {
        if (!addedGardenServices) {
          grouped.add(
            _withMergedData(_virtualGardenServices, source, gardenServiceSlugs),
          );
          addedGardenServices = true;
        }
        continue;
      }

      if (nurseryProductSlugs.contains(category.slug)) {
        if (!addedNurseryProducts) {
          grouped.add(
            _withMergedData(
              _virtualNurseryProducts,
              source,
              nurseryProductSlugs,
            ),
          );
          addedNurseryProducts = true;
        }
        continue;
      }

      grouped.add(category);
    }

    return grouped;
  }

  static Map<String, dynamic> routeArguments(CategoryModel category) {
    final query = queryFor(category);
    return {'category': category, if (query.isNotEmpty) 'query': query};
  }

  static Map<String, dynamic> queryFor(CategoryModel category) {
    final slugs = switch (category.slug) {
      maintenanceSlug => maintenanceSlugs,
      gardenServicesSlug => gardenServiceSlugs,
      nurseryProductsSlug => nurseryProductSlugs,
      _ => const <String>{},
    };

    if (slugs.isEmpty) return const {};

    return {'category_slugs': slugs.join(',')};
  }

  static bool isVirtual(CategoryModel? category) {
    return {
      maintenanceSlug,
      gardenServicesSlug,
      nurseryProductsSlug,
    }.contains(category?.slug);
  }

  static bool usesSimpleServiceLayout(CategoryModel? category) {
    final slug = category?.slug;
    return slug == maintenanceSlug ||
        slug == gardenServicesSlug ||
        maintenanceSlugs.contains(slug) ||
        gardenServiceSlugs.contains(slug) ||
        category?.groupKey == 'services';
  }

  static bool usesProductLayout(CategoryModel? category) {
    final slug = category?.slug;
    return slug == nurseryProductsSlug || nurseryProductSlugs.contains(slug);
  }

  static bool showsAvailability(CategoryModel? category) {
    if (usesSimpleServiceLayout(category) || usesProductLayout(category)) {
      return false;
    }

    return category?.supportsBooking ?? true;
  }

  static String subtitleFor(CategoryModel category) {
    if (category.slug == maintenanceSlug) {
      return 'فنيون، عمال وصيانة منزلية';
    }
    if (category.slug == gardenServicesSlug) {
      return 'تنسيق، ري، صيانة وقص أشجار';
    }
    if (category.slug == nurseryProductsSlug) {
      return 'نباتات، أشجار ومستلزمات زراعية';
    }

    return switch (category.groupKey) {
      'bookings' => 'حجوزات وتأجير',
      'entertainment-tourism' => 'ترفيه وسياحة',
      'real-estate' => 'عقارات وتجاري',
      'services' => 'خدمات',
      'garden-nursery' => 'حدائق ومشاتل',
      _ => 'خدمات وإعلانات',
    };
  }

  static IconData iconFor(CategoryModel category) {
    return switch (category.slug) {
      'chalets' => Icons.home_work_outlined,
      'sports-fields' => Icons.sports_soccer_outlined,
      'wedding-halls' => Icons.celebration_outlined,
      'wedding-supplies' => Icons.inventory_2_outlined,
      'cars' => Icons.directions_car_outlined,
      'buses' => Icons.directions_bus_outlined,
      'hotels' => Icons.apartment_outlined,
      'tourism-offices' || 'travel-agencies' => Icons.flight_takeoff_outlined,
      'turkish-baths' => Icons.spa_outlined,
      'amusement-parks' ||
      'indoor-amusement-parks' ||
      'water-parks' => Icons.attractions_outlined,
      'parks' => Icons.park_outlined,
      'tourist-transport-companies' => Icons.tour_outlined,
      'airlines' => Icons.flight_outlined,
      'international-parcel-offices' => Icons.local_shipping_outlined,
      'apartments-rent' => Icons.meeting_room_outlined,
      'commercial-complexes' ||
      'commercial-offices' ||
      'commercial-shops' => Icons.store_mall_directory_outlined,
      maintenanceSlug => Icons.handyman_outlined,
      gardenServicesSlug => Icons.yard_outlined,
      nurseryProductsSlug => Icons.local_florist_outlined,
      _ => Icons.grid_view_outlined,
    };
  }

  static CategoryModel _withMergedData(
    CategoryModel base,
    List<CategoryModel> source,
    Set<String> slugs,
  ) {
    final matched = source.where((category) => slugs.contains(category.slug));
    final filtersByKey = <String, CategoryFilterModel>{};
    var count = 0;

    for (final category in matched) {
      count += category.listingsCount ?? 0;
      for (final filter in category.filters) {
        filtersByKey.putIfAbsent(filter.key, () => filter);
      }
    }

    return CategoryModel(
      id: base.id,
      nameAr: base.nameAr,
      slug: base.slug,
      nameEn: base.nameEn,
      groupKey: base.groupKey,
      icon: base.icon,
      supportsBooking: base.supportsBooking,
      listingsCount: count == 0 ? null : count,
      filters: filtersByKey.values.toList(),
    );
  }
}
