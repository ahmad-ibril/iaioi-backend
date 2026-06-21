class CategoryModel {
  const CategoryModel({
    required this.id,
    required this.nameAr,
    required this.slug,
    this.nameEn,
    this.groupKey,
    this.icon,
    this.supportsBooking = true,
    this.listingsCount,
    this.filters = const [],
  });

  final int id;
  final String nameAr;
  final String? nameEn;
  final String slug;
  final String? groupKey;
  final String? icon;
  final bool supportsBooking;
  final int? listingsCount;
  final List<CategoryFilterModel> filters;

  factory CategoryModel.fromJson(Map<String, dynamic> json) {
    return CategoryModel(
      id: json['id'] ?? 0,
      nameAr: json['name_ar'] ?? '',
      nameEn: json['name_en'],
      slug: json['slug'] ?? '',
      groupKey: json['group_key'],
      icon: json['icon'],
      supportsBooking: json['supports_booking'] ?? true,
      listingsCount: json['listings_count'],
      filters: (json['filters'] as List? ?? [])
          .whereType<Map<String, dynamic>>()
          .map(CategoryFilterModel.fromJson)
          .toList(),
    );
  }
}

class CategoryFilterModel {
  const CategoryFilterModel({
    required this.key,
    required this.labelAr,
    required this.inputType,
    this.labelEn,
    this.options = const [],
    this.unitAr,
  });

  final String key;
  final String labelAr;
  final String? labelEn;
  final String inputType;
  final String? unitAr;
  final List<FilterOptionModel> options;

  factory CategoryFilterModel.fromJson(Map<String, dynamic> json) {
    final values = json['options'] is Map
        ? (json['options']['values'] as List? ?? [])
        : const [];
    return CategoryFilterModel(
      key: json['key'] ?? '',
      labelAr: json['label_ar'] ?? '',
      labelEn: json['label_en'],
      inputType: json['input_type'] ?? 'text',
      unitAr: json['unit_ar'],
      options: values
          .whereType<Map<String, dynamic>>()
          .map(FilterOptionModel.fromJson)
          .toList(),
    );
  }
}

class FilterOptionModel {
  const FilterOptionModel({
    required this.value,
    required this.labelAr,
    this.labelEn,
  });

  final String value;
  final String labelAr;
  final String? labelEn;

  factory FilterOptionModel.fromJson(Map<String, dynamic> json) {
    return FilterOptionModel(
      value: '${json['value'] ?? ''}',
      labelAr: json['label_ar'] ?? '${json['value'] ?? ''}',
      labelEn: json['label_en'],
    );
  }
}
