import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../core/config/app_theme.dart';
import '../../core/utils/category_presentation.dart';
import '../../data/models/category_model.dart';
import '../../data/models/listing_model.dart';
import '../../routes/app_routes.dart';
import '../../shared/widgets/app_search_bar.dart';
import '../../shared/widgets/category_card.dart';
import '../../shared/widgets/empty_state.dart';
import '../../shared/widgets/google_ad_placeholder.dart';
import '../../shared/widgets/home_hero_slider.dart';
import '../../shared/widgets/listing_card.dart';
import '../../shared/widgets/main_bottom_navigation.dart';
import '../../shared/widgets/section_header.dart';
import '../../shared/widgets/sponsored_ads_widget.dart';
import '../auth/auth_controller.dart';
import 'home_controller.dart';

class HomePage extends GetView<HomeController> {
  const HomePage({super.key});

  @override
  Widget build(BuildContext context) {
    final auth = Get.find<UserAuthController>();

    return Scaffold(
      body: SafeArea(
        child: RefreshIndicator(
          onRefresh: controller.loadHome,
          child: Obx(() {
            final categories = CategoryPresentation.groupedCategories(
              controller.categories,
            );
            final listings = controller.latestListings.toList();

            if (controller.isLoading.value && categories.isEmpty) {
              return const Center(child: CircularProgressIndicator());
            }
            if (controller.error.value != null && categories.isEmpty) {
              return EmptyState(
                message: controller.error.value!,
                onRetry: controller.loadHome,
              );
            }

            return Center(
              child: ConstrainedBox(
                constraints: const BoxConstraints(maxWidth: 960),
                child: ListView(
                  padding: const EdgeInsets.fromLTRB(16, 10, 16, 24),
                  children: [
                    _SponsoredOpenGate(enabled: listings.isNotEmpty),
                    _TopBar(auth: auth),
                    const SizedBox(height: 14),
                    HomeHeroSlider(
                      onActionTap: () => Get.toNamed(AppRoutes.allListings),
                    ),
                    const SizedBox(height: 14),
                    AppSearchBar(
                      readOnly: true,
                      hint: 'ابحث عن شاليه، سيارة، فندق أو خدمة...',
                      onTap: () => Get.toNamed(AppRoutes.allListings),
                      onFilterTap: () => Get.toNamed(AppRoutes.allListings),
                    ),
                    const GoogleAdPlaceholder(
                      type: GoogleAdPlaceholderType.banner,
                      label: 'إعلان Google بعد البانر',
                    ),
                    const SponsoredAdsWidget(
                      placement: SponsoredAdPlacement.homeTop,
                    ),
                    const SizedBox(height: 8),
                    SectionHeader(
                      title: 'الأقسام الرئيسية',
                      subtitle: 'اختر نوع الخدمة التي تبحث عنها',
                      action: 'عرض الكل',
                      onActionTap: () => Get.toNamed(AppRoutes.categories),
                    ),
                    const SizedBox(height: 10),
                    _HorizontalCategories(
                      categories: categories.take(10).toList(),
                    ),
                    const SponsoredAdsWidget(
                      placement: SponsoredAdPlacement.betweenCategories,
                    ),
                    _HomeListingSection(
                      title: 'العروض المميزة',
                      subtitle: 'خيارات مختارة ومناسبة للحجز السريع',
                      listings: _featuredListings(listings),
                      horizontal: true,
                    ),
                    _HomeListingSection(
                      title: 'أحدث الإعلانات',
                      subtitle: 'آخر ما أضيف على المنصة',
                      listings: listings.take(4).toList(),
                    ),
                    _HomeListingSection(
                      title: 'الأكثر مشاهدة',
                      subtitle: 'إعلانات يزورها المستخدمون كثيراً',
                      listings: _mostViewedListings(listings),
                      horizontal: true,
                    ),
                    _HomeListingSection(
                      title: 'متاح اليوم',
                      subtitle: 'إعلانات لديها فترات متاحة اليوم',
                      listings: _availableTodayListings(listings),
                      horizontal: true,
                      emptyMessage: 'لا توجد إعلانات مؤكدة كمتاحة اليوم.',
                    ),
                    const SizedBox(height: 8),
                    FilledButton.icon(
                      onPressed: () => Get.toNamed(AppRoutes.allListings),
                      icon: const Icon(Icons.grid_view_rounded),
                      label: const Text('عرض كل الإعلانات'),
                    ),
                  ],
                ),
              ),
            );
          }),
        ),
      ),
      bottomNavigationBar: const MainBottomNavigation(selectedIndex: 0),
    );
  }
}

class _SponsoredOpenGate extends StatefulWidget {
  const _SponsoredOpenGate({required this.enabled});

  final bool enabled;

  @override
  State<_SponsoredOpenGate> createState() => _SponsoredOpenGateState();
}

class _SponsoredOpenGateState extends State<_SponsoredOpenGate> {
  static bool _shown = false;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) => _maybeShow());
  }

  @override
  void didUpdateWidget(covariant _SponsoredOpenGate oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (!oldWidget.enabled && widget.enabled) {
      WidgetsBinding.instance.addPostFrameCallback((_) => _maybeShow());
    }
  }

  void _maybeShow() {
    if (!mounted || _shown || !widget.enabled) return;
    _shown = true;
    Get.toNamed(AppRoutes.sponsoredAds);
  }

  @override
  Widget build(BuildContext context) => const SizedBox.shrink();
}

class _TopBar extends StatelessWidget {
  const _TopBar({required this.auth});

  final UserAuthController auth;

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                'حجوزات وتأجير',
                style: Theme.of(context).textTheme.titleLarge,
              ),
              const SizedBox(height: 2),
              Row(
                children: [
                  const Icon(
                    Icons.place_outlined,
                    size: 16,
                    color: AppTheme.primary,
                  ),
                  const SizedBox(width: 4),
                  Text('الأردن', style: Theme.of(context).textTheme.bodySmall),
                ],
              ),
            ],
          ),
        ),
        IconButton.filledTonal(
          onPressed: () => Get.toNamed(AppRoutes.favorites),
          icon: const Icon(Icons.favorite_border_rounded),
          tooltip: 'المفضلة',
        ),
        const SizedBox(width: 6),
        Obx(
          () => IconButton.filledTonal(
            onPressed: () => Get.toNamed(AppRoutes.account),
            icon: Icon(
              auth.isAuthenticated
                  ? Icons.account_circle_rounded
                  : Icons.person_outline_rounded,
            ),
            tooltip: 'حسابي',
          ),
        ),
      ],
    );
  }
}

class _HorizontalCategories extends StatelessWidget {
  const _HorizontalCategories({required this.categories});

  final List<CategoryModel> categories;

  @override
  Widget build(BuildContext context) {
    if (categories.isEmpty) return const SizedBox.shrink();

    return SizedBox(
      height: 144,
      child: ListView.separated(
        scrollDirection: Axis.horizontal,
        itemCount: categories.length,
        separatorBuilder: (context, index) => const SizedBox(width: 10),
        itemBuilder: (context, index) {
          final category = categories[index];
          return SizedBox(
            width: 142,
            child: CategoryCard(
              category: category,
              onTap: () => Get.toNamed(
                AppRoutes.listings,
                arguments: CategoryPresentation.routeArguments(category),
              ),
            ),
          );
        },
      ),
    );
  }
}

class _HomeListingSection extends StatelessWidget {
  const _HomeListingSection({
    required this.title,
    required this.subtitle,
    required this.listings,
    this.horizontal = false,
    this.emptyMessage = 'لا توجد إعلانات بعد.',
  });

  final String title;
  final String subtitle;
  final List<ListingModel> listings;
  final bool horizontal;
  final String emptyMessage;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(top: 18),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SectionHeader(
            title: title,
            subtitle: subtitle,
            action: 'عرض كل الإعلانات',
            onActionTap: () => Get.toNamed(AppRoutes.allListings),
          ),
          const SizedBox(height: 10),
          if (listings.isEmpty)
            EmptyState(message: emptyMessage)
          else if (horizontal)
            _ListingsStrip(listings: listings)
          else
            ...listings.map((listing) => ListingCard(listing: listing)),
        ],
      ),
    );
  }
}

class _ListingsStrip extends StatelessWidget {
  const _ListingsStrip({required this.listings});

  final List<ListingModel> listings;

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      height: 154,
      child: ListView.separated(
        scrollDirection: Axis.horizontal,
        itemCount: listings.take(6).length,
        separatorBuilder: (context, index) => const SizedBox(width: 10),
        itemBuilder: (context, index) {
          final listing = listings[index];
          return SizedBox(
            width: 285,
            child: ListingCard(listing: listing, compact: true),
          );
        },
      ),
    );
  }
}

List<ListingModel> _featuredListings(List<ListingModel> source) {
  final featured = source
      .where((listing) => listing.isFeatured || listing.hasSpecialOffer)
      .toList();
  return featured.isEmpty ? source.take(6).toList() : featured.take(6).toList();
}

List<ListingModel> _mostViewedListings(List<ListingModel> source) {
  final sorted = source.toList()
    ..sort((a, b) => (b.viewsCount ?? 0).compareTo(a.viewsCount ?? 0));
  return sorted.take(6).toList();
}

List<ListingModel> _availableTodayListings(List<ListingModel> source) {
  final today = DateUtils.dateOnly(DateTime.now());
  return source
      .where(
        (listing) =>
            listing.availableToday ||
            listing.availabilitySlots.any(
              (slot) =>
                  slot.isAvailable && DateUtils.isSameDay(slot.date, today),
            ),
      )
      .take(6)
      .toList();
}
