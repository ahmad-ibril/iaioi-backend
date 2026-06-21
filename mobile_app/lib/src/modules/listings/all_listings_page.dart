import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../core/config/app_theme.dart';
import '../../core/services/location_service.dart';
import '../../core/utils/category_presentation.dart';
import '../../data/models/category_model.dart';
import '../../data/models/listing_model.dart';
import '../../data/repositories/catalog_repository.dart';
import '../../shared/widgets/app_search_bar.dart';
import '../../shared/widgets/empty_state.dart';
import '../../shared/widgets/filter_bottom_sheet.dart';
import '../../shared/widgets/google_ad_placeholder.dart';
import '../../shared/widgets/listing_card.dart';
import '../../shared/widgets/main_bottom_navigation.dart';
import '../../shared/widgets/section_header.dart';
import '../../shared/widgets/sponsored_ads_widget.dart';
import '../home/home_controller.dart';

class AllListingsPage extends StatefulWidget {
  const AllListingsPage({super.key});

  @override
  State<AllListingsPage> createState() => _AllListingsPageState();
}

class _AllListingsPageState extends State<AllListingsPage> {
  final searchController = TextEditingController();
  final listings = <ListingModel>[];
  final categories = <CategoryModel>[];
  Map<String, dynamic> query = {'per_page': 30};
  bool isLoading = true;
  String? error;

  CatalogRepository get repository => Get.find<CatalogRepository>();

  @override
  void initState() {
    super.initState();
    _load();
  }

  @override
  void dispose() {
    searchController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('كل الإعلانات')),
      body: RefreshIndicator(
        onRefresh: _load,
        child: Center(
          child: ConstrainedBox(
            constraints: const BoxConstraints(maxWidth: 900),
            child: ListView(
              padding: const EdgeInsets.fromLTRB(16, 8, 16, 24),
              children: [
                SectionHeader(
                  title: 'كل الإعلانات',
                  subtitle: 'ابحث، فلتر، واختر الإعلان الأنسب لك',
                  action: 'الفلاتر',
                  onActionTap: _openFilters,
                ),
                const SizedBox(height: 12),
                AppSearchBar(
                  controller: searchController,
                  hint: 'ابحث في جميع الإعلانات...',
                  onSubmitted: _applySearch,
                  onFilterTap: _openFilters,
                ),
                const GoogleAdPlaceholder(
                  type: GoogleAdPlaceholderType.banner,
                  label: 'مساحة Google Ads داخل صفحة كل الإعلانات',
                ),
                _QuickFilters(
                  query: query,
                  onSort: _applySort,
                  onNearest: _applyNearest,
                  onOpenFilters: _openFilters,
                ),
                const SizedBox(height: 8),
                _ActiveFilters(query: query, onClear: _clearFilters),
                const SizedBox(height: 12),
                if (isLoading && listings.isEmpty)
                  const SizedBox(
                    height: 360,
                    child: Center(child: CircularProgressIndicator()),
                  )
                else if (error != null && listings.isEmpty)
                  EmptyState(message: error!, onRetry: _load)
                else if (listings.isEmpty)
                  EmptyState(message: 'لا توجد إعلانات مطابقة.', onRetry: _load)
                else
                  ..._listingItems(),
              ],
            ),
          ),
        ),
      ),
      bottomNavigationBar: const MainBottomNavigation(selectedIndex: 1),
    );
  }

  Future<void> _load() async {
    setState(() {
      isLoading = true;
      error = null;
    });

    try {
      if (categories.isEmpty) {
        final home = Get.find<HomeController>();
        final source = home.categories.isEmpty
            ? await repository.fetchCategories()
            : home.categories.toList();
        categories
          ..clear()
          ..addAll(CategoryPresentation.groupedCategories(source));
      }

      final result = await repository.fetchListings(query: query);
      setState(() {
        listings
          ..clear()
          ..addAll(result);
      });
    } on DioException catch (exception) {
      setState(() => error = _message(exception));
    } catch (_) {
      setState(() => error = 'تعذر تحميل الإعلانات. تأكد من تشغيل السيرفر.');
    } finally {
      if (mounted) setState(() => isLoading = false);
    }
  }

  void _applySearch(String value) {
    final next = Map<String, dynamic>.from(query);
    final search = value.trim();
    if (search.isEmpty) {
      next.remove('q');
    } else {
      next['q'] = search;
    }
    setState(() => query = next);
    _load();
  }

  Future<void> _openFilters() async {
    final result = await FilterBottomSheet.show(
      context,
      categories: categories,
      initialQuery: query,
    );

    if (result == null) return;
    setState(() {
      query = {'per_page': 30, ...result};
      searchController.text = query['q']?.toString() ?? searchController.text;
    });
    _load();
  }

  void _clearFilters() {
    searchController.clear();
    setState(() => query = {'per_page': 30});
    _load();
  }

  Future<void> _applyNearest() async {
    final position = await Get.find<LocationService>().requestCurrentLocation();
    if (position == null) return;

    setState(() {
      query = {
        ...query,
        'sort': 'distance',
        'latitude': position.latitude,
        'longitude': position.longitude,
      };
    });
    _load();
  }

  List<Widget> _listingItems() {
    final items = <Widget>[];
    for (var index = 0; index < listings.length; index++) {
      if (index > 0 && index % 5 == 0) {
        items.add(
          const GoogleAdPlaceholder(
            type: GoogleAdPlaceholderType.native,
            label: 'إعلان Google بين كروت الإعلانات',
          ),
        );
      }
      if (index == 2) {
        items.add(
          const SponsoredAdsWidget(
            placement: SponsoredAdPlacement.betweenListings,
          ),
        );
      }
      items.add(ListingCard(listing: listings[index]));
    }
    return items;
  }

  void _applySort(String value) {
    final next = Map<String, dynamic>.from(query);
    if (value != 'distance') {
      next.remove('latitude');
      next.remove('longitude');
    }
    if (value == 'newest') {
      next.remove('sort');
    } else {
      next['sort'] = value;
    }
    setState(() => query = next);
    _load();
  }

  String _message(DioException exception) {
    final data = exception.response?.data;
    if (data is Map<String, dynamic>) {
      final message = data['message'];
      if (message is String && message.isNotEmpty) return message;
    }
    return 'تعذر الاتصال بالخادم.';
  }
}

class _QuickFilters extends StatelessWidget {
  const _QuickFilters({
    required this.query,
    required this.onSort,
    required this.onNearest,
    required this.onOpenFilters,
  });

  final Map<String, dynamic> query;
  final ValueChanged<String> onSort;
  final VoidCallback onNearest;
  final VoidCallback onOpenFilters;

  @override
  Widget build(BuildContext context) {
    final selected = query['sort']?.toString() ?? 'newest';

    return SingleChildScrollView(
      scrollDirection: Axis.horizontal,
      padding: const EdgeInsets.only(top: 2),
      child: Row(
        children: [
          _FilterChipButton(
            label: 'الأحدث',
            icon: Icons.schedule_rounded,
            selected: selected == 'newest',
            onTap: () => onSort('newest'),
          ),
          _FilterChipButton(
            label: 'الأقل سعراً',
            icon: Icons.south_rounded,
            selected: selected == 'price_asc',
            onTap: () => onSort('price_asc'),
          ),
          _FilterChipButton(
            label: 'الأعلى سعراً',
            icon: Icons.north_rounded,
            selected: selected == 'price_desc',
            onTap: () => onSort('price_desc'),
          ),
          _FilterChipButton(
            label: 'الأقرب إليك',
            icon: Icons.near_me_outlined,
            selected: selected == 'distance',
            onTap: onNearest,
          ),
          _FilterChipButton(
            label: 'القسم والمدينة والسعر',
            icon: Icons.tune_rounded,
            selected: false,
            onTap: onOpenFilters,
          ),
        ],
      ),
    );
  }
}

class _FilterChipButton extends StatelessWidget {
  const _FilterChipButton({
    required this.label,
    required this.icon,
    required this.selected,
    required this.onTap,
  });

  final String label;
  final IconData icon;
  final bool selected;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final foreground = selected ? Colors.white : AppTheme.textDark;
    return Padding(
      padding: const EdgeInsetsDirectional.only(end: 8),
      child: ActionChip(
        onPressed: onTap,
        avatar: Icon(icon, size: 18, color: foreground),
        label: Text(label),
        labelStyle: TextStyle(
          color: foreground,
          fontWeight: selected ? FontWeight.w800 : FontWeight.w700,
        ),
        backgroundColor: selected ? AppTheme.primary : Colors.white,
        side: BorderSide(color: selected ? AppTheme.primary : AppTheme.border),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
      ),
    );
  }
}

class _ActiveFilters extends StatelessWidget {
  const _ActiveFilters({required this.query, required this.onClear});

  final Map<String, dynamic> query;
  final VoidCallback onClear;

  @override
  Widget build(BuildContext context) {
    final active = query.entries
        .where(
          (entry) => entry.key != 'per_page' && '${entry.value}'.isNotEmpty,
        )
        .toList();

    if (active.isEmpty) return const SizedBox.shrink();

    return Wrap(
      spacing: 8,
      runSpacing: 8,
      children: [
        for (final entry in active)
          Chip(label: Text(_label(entry.key, entry.value))),
        ActionChip(
          avatar: const Icon(Icons.close, size: 18),
          label: const Text('مسح'),
          onPressed: onClear,
        ),
      ],
    );
  }

  String _label(String key, Object? value) {
    final label = switch (key) {
      'q' => 'بحث',
      'category' => 'قسم',
      'area' => 'منطقة',
      'min_price' => 'من',
      'max_price' => 'إلى',
      'sort' => 'ترتيب',
      'available_today' => 'متاح اليوم',
      _ => key,
    };

    return '$label: $value';
  }
}
