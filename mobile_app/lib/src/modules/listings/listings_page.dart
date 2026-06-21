import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../core/config/app_theme.dart';
import '../../core/services/location_service.dart';
import '../../core/utils/category_presentation.dart';
import '../../data/models/category_model.dart';
import '../../data/repositories/catalog_repository.dart';
import '../../routes/app_routes.dart';
import '../../shared/widgets/app_search_bar.dart';
import '../../shared/widgets/empty_state.dart';
import '../../shared/widgets/listing_card.dart';
import '../../shared/widgets/main_bottom_navigation.dart';
import 'listings_controller.dart';

class CategoryListingsPage extends ListingsPage {
  const CategoryListingsPage({super.key});
}

class ListingsPage extends StatelessWidget {
  const ListingsPage({super.key});

  @override
  Widget build(BuildContext context) {
    final args = (Get.arguments as Map?) ?? {};
    final category = args['category'] as CategoryModel?;
    final initialQuery = Map<String, dynamic>.from(args['query'] as Map? ?? {});
    final controller = Get.put(
      ListingsController(Get.find<CatalogRepository>(), category, initialQuery),
      tag:
          '${category?.slug ?? 'all'}-${DateTime.now().microsecondsSinceEpoch}',
    );
    final productLayout = CategoryPresentation.usesProductLayout(category);
    final searchController = TextEditingController(
      text: controller.query['q']?.toString() ?? '',
    );

    return Scaffold(
      appBar: AppBar(
        title: Text(category?.nameAr ?? 'إعلانات القسم'),
        actions: [
          IconButton(
            onPressed: () async {
              final filters = await Get.toNamed(
                AppRoutes.filters,
                arguments: {'category': category, 'query': controller.query},
              );
              if (filters is Map<String, dynamic>) {
                controller.applyFilters(filters);
              }
            },
            icon: const Icon(Icons.tune),
            tooltip: 'الفلاتر',
          ),
        ],
      ),
      body: Column(
        children: [
          Center(
            child: ConstrainedBox(
              constraints: const BoxConstraints(maxWidth: 760),
              child: Padding(
                padding: const EdgeInsets.fromLTRB(16, 4, 16, 12),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    AppSearchBar(
                      controller: searchController,
                      hint: 'ابحث داخل هذا القسم...',
                      onSubmitted: (value) {
                        final next = Map<String, dynamic>.from(
                          controller.query,
                        );
                        final search = value.trim();
                        if (search.isEmpty) {
                          next.remove('q');
                        } else {
                          next['q'] = search;
                        }
                        controller.applyFilters(next);
                      },
                      onFilterTap: () async {
                        final filters = await Get.toNamed(
                          AppRoutes.filters,
                          arguments: {
                            'category': category,
                            'query': controller.query,
                          },
                        );
                        if (filters is Map<String, dynamic>) {
                          controller.applyFilters(filters);
                        }
                      },
                    ),
                    const SizedBox(height: 10),
                    _SortChips(controller: controller),
                  ],
                ),
              ),
            ),
          ),
          Expanded(
            child: Obx(() {
              if (controller.isLoading.value && controller.listings.isEmpty) {
                return const Center(child: CircularProgressIndicator());
              }
              if (controller.error.value != null &&
                  controller.listings.isEmpty) {
                return EmptyState(
                  message: controller.error.value!,
                  onRetry: controller.loadListings,
                );
              }
              if (controller.listings.isEmpty) {
                return const EmptyState(message: 'لا توجد إعلانات مطابقة.');
              }

              return RefreshIndicator(
                onRefresh: controller.loadListings,
                child: Center(
                  child: ConstrainedBox(
                    constraints: const BoxConstraints(maxWidth: 760),
                    child: productLayout
                        ? GridView.builder(
                            padding: const EdgeInsets.fromLTRB(16, 0, 16, 20),
                            itemCount: controller.listings.length,
                            gridDelegate:
                                const SliverGridDelegateWithFixedCrossAxisCount(
                                  crossAxisCount: 2,
                                  crossAxisSpacing: 12,
                                  mainAxisSpacing: 12,
                                  childAspectRatio: 0.78,
                                ),
                            itemBuilder: (context, index) => ListingCard(
                              listing: controller.listings[index],
                              style: ListingCardStyle.product,
                            ),
                          )
                        : ListView.builder(
                            padding: const EdgeInsets.fromLTRB(16, 0, 16, 20),
                            itemCount: controller.listings.length,
                            itemBuilder: (context, index) => ListingCard(
                              listing: controller.listings[index],
                            ),
                          ),
                  ),
                ),
              );
            }),
          ),
        ],
      ),
      bottomNavigationBar: const MainBottomNavigation(selectedIndex: 2),
    );
  }
}

class _SortChips extends StatelessWidget {
  const _SortChips({required this.controller});

  final ListingsController controller;

  @override
  Widget build(BuildContext context) {
    return Obx(() {
      final selected = controller.query['sort']?.toString() ?? 'newest';
      return SingleChildScrollView(
        scrollDirection: Axis.horizontal,
        child: Row(
          children: [
            _SortChip(
              label: 'الأحدث',
              value: 'newest',
              selected: selected == 'newest',
              onSelected: _apply,
            ),
            _SortChip(
              label: 'الأقل سعرا',
              value: 'price_asc',
              selected: selected == 'price_asc',
              onSelected: _apply,
            ),
            _SortChip(
              label: 'الأعلى سعرا',
              value: 'price_desc',
              selected: selected == 'price_desc',
              onSelected: _apply,
            ),
            _SortChip(
              label: 'الأقرب',
              value: 'distance',
              selected: selected == 'distance',
              onSelected: _apply,
            ),
          ],
        ),
      );
    });
  }

  Future<void> _apply(String value) async {
    final next = Map<String, dynamic>.from(controller.query);
    if (value == 'newest') {
      next.remove('sort');
      next.remove('latitude');
      next.remove('longitude');
    } else {
      next['sort'] = value;
      if (value == 'distance') {
        final position = await Get.find<LocationService>()
            .requestCurrentLocation();
        if (position != null) {
          next['latitude'] = position.latitude;
          next['longitude'] = position.longitude;
        }
      }
    }
    controller.applyFilters(next);
  }
}

class _SortChip extends StatelessWidget {
  const _SortChip({
    required this.label,
    required this.value,
    required this.selected,
    required this.onSelected,
  });

  final String label;
  final String value;
  final bool selected;
  final Future<void> Function(String value) onSelected;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsetsDirectional.only(end: 8),
      child: ChoiceChip(
        label: Text(label),
        selected: selected,
        selectedColor: AppTheme.primary,
        backgroundColor: Colors.white,
        side: const BorderSide(color: AppTheme.border),
        labelStyle: TextStyle(
          color: selected ? Colors.white : AppTheme.textMuted,
          fontWeight: FontWeight.w800,
        ),
        onSelected: (_) {
          onSelected(value);
        },
      ),
    );
  }
}
