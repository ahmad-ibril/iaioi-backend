import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../core/config/app_theme.dart';
import '../../data/models/category_model.dart';

class FilterBottomSheet extends StatefulWidget {
  const FilterBottomSheet({
    super.key,
    required this.categories,
    required this.initialQuery,
  });

  final List<CategoryModel> categories;
  final Map<String, dynamic> initialQuery;

  static Future<Map<String, dynamic>?> show(
    BuildContext context, {
    required List<CategoryModel> categories,
    required Map<String, dynamic> initialQuery,
  }) {
    return showModalBottomSheet<Map<String, dynamic>>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.white,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(8)),
      ),
      builder: (_) =>
          FilterBottomSheet(categories: categories, initialQuery: initialQuery),
    );
  }

  @override
  State<FilterBottomSheet> createState() => _FilterBottomSheetState();
}

class _FilterBottomSheetState extends State<FilterBottomSheet> {
  late final TextEditingController cityController;
  late final TextEditingController minPriceController;
  late final TextEditingController maxPriceController;
  String? categorySlug;
  String sort = 'newest';
  bool availableToday = false;

  @override
  void initState() {
    super.initState();
    final query = widget.initialQuery;
    categorySlug = query['category']?.toString();
    cityController = TextEditingController(
      text: query['area']?.toString() ?? '',
    );
    minPriceController = TextEditingController(
      text: query['min_price']?.toString() ?? '',
    );
    maxPriceController = TextEditingController(
      text: query['max_price']?.toString() ?? '',
    );
    sort = query['sort']?.toString() ?? 'newest';
    availableToday =
        query['available_today'] == true || query['available_today'] == '1';
  }

  @override
  void dispose() {
    cityController.dispose();
    minPriceController.dispose();
    maxPriceController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return SafeArea(
      child: Padding(
        padding: EdgeInsets.fromLTRB(
          16,
          14,
          16,
          16 + MediaQuery.viewInsetsOf(context).bottom,
        ),
        child: SingleChildScrollView(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Center(
                child: Container(
                  width: 42,
                  height: 4,
                  decoration: BoxDecoration(
                    color: AppTheme.border,
                    borderRadius: BorderRadius.circular(8),
                  ),
                ),
              ),
              const SizedBox(height: 14),
              Text(
                'تنظيم النتائج',
                style: Theme.of(context).textTheme.titleLarge,
              ),
              const SizedBox(height: 14),
              DropdownButtonFormField<String>(
                initialValue: categorySlug,
                decoration: const InputDecoration(labelText: 'القسم'),
                items: [
                  const DropdownMenuItem(
                    value: null,
                    child: Text('كل الأقسام'),
                  ),
                  for (final category in widget.categories)
                    DropdownMenuItem(
                      value: category.slug,
                      child: Text(category.nameAr),
                    ),
                ],
                onChanged: (value) => setState(() => categorySlug = value),
              ),
              const SizedBox(height: 12),
              TextField(
                controller: cityController,
                decoration: const InputDecoration(
                  labelText: 'المدينة أو المنطقة',
                  prefixIcon: Icon(Icons.place_outlined),
                ),
              ),
              const SizedBox(height: 12),
              Row(
                children: [
                  Expanded(
                    child: TextField(
                      controller: minPriceController,
                      keyboardType: TextInputType.number,
                      decoration: const InputDecoration(labelText: 'السعر من'),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: TextField(
                      controller: maxPriceController,
                      keyboardType: TextInputType.number,
                      decoration: const InputDecoration(labelText: 'السعر إلى'),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 12),
              DropdownButtonFormField<String>(
                initialValue: sort,
                decoration: const InputDecoration(labelText: 'الترتيب'),
                items: const [
                  DropdownMenuItem(value: 'newest', child: Text('الأحدث')),
                  DropdownMenuItem(
                    value: 'price_asc',
                    child: Text('الأقل سعرا'),
                  ),
                  DropdownMenuItem(
                    value: 'price_desc',
                    child: Text('الأعلى سعرا'),
                  ),
                  DropdownMenuItem(value: 'distance', child: Text('الأقرب')),
                ],
                onChanged: (value) => setState(() => sort = value ?? 'newest'),
              ),
              const SizedBox(height: 8),
              SwitchListTile(
                contentPadding: EdgeInsets.zero,
                value: availableToday,
                title: const Text('متاح اليوم'),
                onChanged: (value) => setState(() => availableToday = value),
              ),
              const SizedBox(height: 14),
              FilledButton.icon(
                onPressed: _apply,
                icon: const Icon(Icons.check),
                label: const Text('عرض النتائج'),
              ),
              const SizedBox(height: 8),
              TextButton(
                onPressed: () => Get.back(result: <String, dynamic>{}),
                child: const Text('مسح الفلاتر'),
              ),
            ],
          ),
        ),
      ),
    );
  }

  void _apply() {
    final query = <String, dynamic>{};

    void put(String key, String value) {
      final trimmed = value.trim();
      if (trimmed.isNotEmpty) query[key] = trimmed;
    }

    if (categorySlug != null && categorySlug!.isNotEmpty) {
      query['category'] = categorySlug;
    }
    put('area', cityController.text);
    put('min_price', minPriceController.text);
    put('max_price', maxPriceController.text);
    if (sort != 'newest') query['sort'] = sort;
    if (availableToday) query['available_today'] = '1';

    Get.back(result: query);
  }
}
