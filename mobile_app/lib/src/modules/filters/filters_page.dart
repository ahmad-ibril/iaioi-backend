import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../data/models/category_model.dart';

class FiltersPage extends StatefulWidget {
  const FiltersPage({super.key});

  @override
  State<FiltersPage> createState() => _FiltersPageState();
}

class _FiltersPageState extends State<FiltersPage> {
  late final CategoryModel? category;
  late final TextEditingController searchController;
  late final TextEditingController areaController;
  late final TextEditingController minPriceController;
  late final TextEditingController maxPriceController;
  late String sort;
  final dynamicValues = <String, dynamic>{};

  @override
  void initState() {
    super.initState();
    final args = (Get.arguments as Map?) ?? {};
    final query = Map<String, dynamic>.from(args['query'] as Map? ?? {});

    category = args['category'] as CategoryModel?;
    searchController = TextEditingController(
      text: query['q']?.toString() ?? '',
    );
    areaController = TextEditingController(
      text: query['area']?.toString() ?? '',
    );
    minPriceController = TextEditingController(
      text: query['min_price']?.toString() ?? '',
    );
    maxPriceController = TextEditingController(
      text: query['max_price']?.toString() ?? '',
    );
    sort = query['sort']?.toString() ?? 'newest';

    final currentFilters = query['filters'];
    if (currentFilters is Map) {
      dynamicValues.addAll(Map<String, dynamic>.from(currentFilters));
    }
  }

  @override
  void dispose() {
    searchController.dispose();
    areaController.dispose();
    minPriceController.dispose();
    maxPriceController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final filters = category?.filters ?? const <CategoryFilterModel>[];

    return Scaffold(
      appBar: AppBar(title: const Text('الفلاتر')),
      body: Center(
        child: ConstrainedBox(
          constraints: const BoxConstraints(maxWidth: 620),
          child: ListView(
            padding: const EdgeInsets.all(16),
            children: [
              TextField(
                controller: searchController,
                decoration: const InputDecoration(
                  prefixIcon: Icon(Icons.search),
                  labelText: 'بحث',
                ),
              ),
              const SizedBox(height: 12),
              TextField(
                controller: areaController,
                decoration: const InputDecoration(
                  prefixIcon: Icon(Icons.place_outlined),
                  labelText: 'المدينة أو المنطقة',
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
              if (filters.isNotEmpty) ...[
                const SizedBox(height: 20),
                Text(
                  'فلاتر القسم',
                  style: Theme.of(context).textTheme.titleLarge,
                ),
                const SizedBox(height: 8),
                for (final filter in filters) ...[
                  _DynamicFilterField(
                    filter: filter,
                    value: dynamicValues[filter.key],
                    onChanged: (value) => setState(() {
                      if (value == null || value == '') {
                        dynamicValues.remove(filter.key);
                      } else {
                        dynamicValues[filter.key] = value;
                      }
                    }),
                  ),
                  const SizedBox(height: 12),
                ],
              ],
              const SizedBox(height: 20),
              FilledButton.icon(
                onPressed: _apply,
                icon: const Icon(Icons.check),
                label: const Text('تطبيق الفلاتر'),
              ),
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

    void putText(String key, TextEditingController controller) {
      final value = controller.text.trim();
      if (value.isNotEmpty) query[key] = value;
    }

    putText('q', searchController);
    putText('area', areaController);
    putText('min_price', minPriceController);
    putText('max_price', maxPriceController);
    if (sort != 'newest') query['sort'] = sort;
    if (dynamicValues.isNotEmpty) {
      query['filters'] = Map<String, dynamic>.from(dynamicValues);
    }

    Get.back(result: query);
  }
}

class _DynamicFilterField extends StatelessWidget {
  const _DynamicFilterField({
    required this.filter,
    required this.value,
    required this.onChanged,
  });

  final CategoryFilterModel filter;
  final dynamic value;
  final ValueChanged<dynamic> onChanged;

  @override
  Widget build(BuildContext context) {
    if (filter.inputType == 'boolean') {
      return SwitchListTile(
        value: value == true || value == 'true',
        onChanged: onChanged,
        title: Text(filter.labelAr),
        contentPadding: EdgeInsets.zero,
      );
    }

    if (filter.options.isNotEmpty || filter.inputType == 'select') {
      return DropdownButtonFormField<String>(
        initialValue: value?.toString().isEmpty == false
            ? value.toString()
            : null,
        decoration: InputDecoration(labelText: filter.labelAr),
        items: [
          for (final option in filter.options)
            DropdownMenuItem(value: option.value, child: Text(option.labelAr)),
        ],
        onChanged: onChanged,
      );
    }

    return TextFormField(
      initialValue: value?.toString() ?? '',
      keyboardType: filter.inputType == 'number' || filter.inputType == 'rating'
          ? TextInputType.number
          : TextInputType.text,
      decoration: InputDecoration(
        labelText: filter.labelAr,
        suffixText: filter.unitAr,
      ),
      onChanged: onChanged,
    );
  }
}
