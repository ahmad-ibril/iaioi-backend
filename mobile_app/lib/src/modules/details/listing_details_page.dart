import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../core/config/app_theme.dart';
import '../../core/services/launch_service.dart';
import '../../core/utils/category_presentation.dart';
import '../../data/models/availability_slot_model.dart';
import '../../data/models/listing_model.dart';
import '../../data/repositories/catalog_repository.dart';
import '../../routes/app_routes.dart';
import '../../shared/widgets/availability_calendar.dart';
import '../../shared/widgets/empty_state.dart';
import '../../shared/widgets/google_ad_placeholder.dart';
import '../../shared/widgets/listing_card.dart';
import '../../shared/widgets/sponsored_ads_widget.dart';
import '../auth/auth_controller.dart';
import '../bookings/booking_controller.dart';
import '../favorites/favorites_controller.dart';
import 'listing_details_controller.dart';

class ListingDetailsPage extends StatelessWidget {
  const ListingDetailsPage({super.key});

  @override
  Widget build(BuildContext context) {
    final args = (Get.arguments as Map?) ?? {};
    final initialListing = args['listing'] as ListingModel?;
    final slug = (args['slug'] as String?) ?? initialListing?.slug ?? '';

    if (slug.isEmpty) {
      return const Scaffold(body: EmptyState(message: 'الإعلان غير موجود.'));
    }

    final controller = Get.put(
      ListingDetailsController(
        Get.find<CatalogRepository>(),
        slug,
        initialListing: initialListing,
      ),
      tag: slug,
    );

    return Scaffold(
      body: Obx(() {
        final listing = controller.listing.value;

        if (controller.isLoading.value && listing == null) {
          return const Center(child: CircularProgressIndicator());
        }
        if (controller.error.value != null && listing == null) {
          return EmptyState(
            message: controller.error.value!,
            onRetry: controller.loadDetails,
          );
        }
        if (listing == null) {
          return const EmptyState(message: 'الإعلان غير متاح حالياً.');
        }

        return CustomScrollView(
          slivers: [
            _DetailsHeader(listing: listing),
            SliverToBoxAdapter(
              child: Center(
                child: ConstrainedBox(
                  constraints: const BoxConstraints(maxWidth: 760),
                  child: Padding(
                    padding: const EdgeInsets.fromLTRB(16, 16, 16, 28),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        _TitleBlock(listing: listing),
                        const SizedBox(height: 16),
                        _ContactActions(listing: listing),
                        if (listing.latitude != null &&
                            listing.longitude != null) ...[
                          const SizedBox(height: 16),
                          _LocationBox(listing: listing),
                        ],
                        if ((listing.descriptionAr ?? '').isNotEmpty) ...[
                          const SizedBox(height: 22),
                          _SectionTitle('الوصف'),
                          const SizedBox(height: 8),
                          Text(
                            listing.descriptionAr!,
                            style: Theme.of(context).textTheme.bodyLarge,
                          ),
                        ],
                        if (listing.attributes.isNotEmpty) ...[
                          const SizedBox(height: 22),
                          _SectionTitle('تفاصيل الإعلان'),
                          const SizedBox(height: 8),
                          _AttributesGrid(listing: listing),
                        ],
                        if (listing.features.isNotEmpty) ...[
                          const SizedBox(height: 22),
                          _SectionTitle('المميزات والإضافات'),
                          const SizedBox(height: 8),
                          _FeaturesWrap(features: listing.features),
                        ],
                        if (CategoryPresentation.showsAvailability(
                          listing.category,
                        )) ...[
                          const SizedBox(height: 22),
                          _SectionTitle('اختر موعد الحجز'),
                          const SizedBox(height: 8),
                          _BookingPickerSection(
                            listing: listing,
                            slots: controller.availability.toList(),
                            onSubmitted: controller.loadDetails,
                          ),
                        ],
                        const SizedBox(height: 22),
                        _SectionTitle('إعلانات مشابهة'),
                        const SizedBox(height: 8),
                        ListingCard(listing: listing, compact: true),
                        const SponsoredAdsWidget(
                          placement: SponsoredAdPlacement.listingDetails,
                        ),
                        const GoogleAdPlaceholder(
                          type: GoogleAdPlaceholderType.details,
                          label: 'إعلان Google أسفل صفحة التفاصيل',
                        ),
                      ],
                    ),
                  ),
                ),
              ),
            ),
          ],
        );
      }),
    );
  }
}

class _BookingPickerSection extends StatefulWidget {
  const _BookingPickerSection({
    required this.listing,
    required this.slots,
    required this.onSubmitted,
  });

  final ListingModel listing;
  final List<AvailabilitySlotModel> slots;
  final Future<void> Function() onSubmitted;

  @override
  State<_BookingPickerSection> createState() => _BookingPickerSectionState();
}

class _BookingPickerSectionState extends State<_BookingPickerSection> {
  DateTime? selectedDate;
  AvailabilitySlotModel? selectedSlot;

  @override
  void initState() {
    super.initState();
    selectedDate = _firstAvailableDate();
  }

  @override
  void didUpdateWidget(covariant _BookingPickerSection oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (oldWidget.slots != widget.slots && selectedDate == null) {
      selectedDate = _firstAvailableDate();
    }
  }

  @override
  Widget build(BuildContext context) {
    final daySlots = selectedDate == null
        ? const <AvailabilitySlotModel>[]
        : widget.slots
              .where((slot) => DateUtils.isSameDay(slot.date, selectedDate))
              .toList();
    final booking = Get.find<BookingController>();

    if (widget.slots.isEmpty) {
      return Text(
        'لا توجد فترات متاحة حالياً.',
        style: Theme.of(context).textTheme.bodySmall,
      );
    }

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          'اختر يوماً ثم اختر الفترة المناسبة. الطلب سيصل لصاحب الإعلان للمراجعة.',
          style: Theme.of(context).textTheme.bodySmall,
        ),
        const SizedBox(height: 10),
        AvailabilityCalendar(
          slots: widget.slots,
          selectedDates: selectedDate == null ? const {} : {selectedDate!},
          onDayTap: (day) {
            setState(() {
              selectedDate = day;
              selectedSlot = null;
            });
          },
        ),
        const SizedBox(height: 12),
        if (daySlots.isEmpty)
          Text(
            'لا توجد فترات في هذا اليوم.',
            style: Theme.of(context).textTheme.bodySmall,
          )
        else
          Wrap(
            spacing: 8,
            runSpacing: 8,
            children: [
              for (final slot in daySlots)
                _SlotChoice(
                  slot: slot,
                  selected: selectedSlot?.id == slot.id,
                  onTap: slot.isAvailable
                      ? () => setState(() => selectedSlot = slot)
                      : null,
                ),
            ],
          ),
        const SizedBox(height: 12),
        Obx(
          () => SizedBox(
            width: double.infinity,
            child: FilledButton.icon(
              onPressed: selectedSlot == null || booking.isSubmitting.value
                  ? null
                  : () => _submit(booking),
              icon: booking.isSubmitting.value
                  ? const SizedBox(
                      width: 18,
                      height: 18,
                      child: CircularProgressIndicator(strokeWidth: 2),
                    )
                  : const Icon(Icons.event_available_outlined),
              label: const Text('طلب حجز'),
            ),
          ),
        ),
      ],
    );
  }

  DateTime? _firstAvailableDate() {
    final available = widget.slots.where((slot) => slot.isAvailable).toList()
      ..sort((a, b) => a.date.compareTo(b.date));
    return available.isEmpty ? null : DateUtils.dateOnly(available.first.date);
  }

  Future<void> _submit(BookingController booking) async {
    final auth = Get.find<UserAuthController>();
    if (!auth.isAuthenticated) {
      Get.toNamed(AppRoutes.login);
      return;
    }

    final slot = selectedSlot;
    if (slot == null) return;

    final success = await booking.create(
      listingId: widget.listing.id,
      availabilitySlotId: slot.id > 0 ? slot.id : null,
      dateFrom: slot.date,
      dateTo: slot.date,
      contactName: auth.user.value?.name,
      contactPhone: auth.user.value?.phone ?? auth.user.value?.whatsapp,
    );

    if (success) {
      setState(() => selectedSlot = null);
      await widget.onSubmitted();
    }
  }
}

class _SlotChoice extends StatelessWidget {
  const _SlotChoice({
    required this.slot,
    required this.selected,
    required this.onTap,
  });

  final AvailabilitySlotModel slot;
  final bool selected;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    final color = slot.statusColor;
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(8),
      child: DecoratedBox(
        decoration: BoxDecoration(
          color: selected
              ? AppTheme.primary.withValues(alpha: 0.12)
              : color.withValues(alpha: 0.1),
          border: Border.all(color: selected ? AppTheme.primary : color),
          borderRadius: BorderRadius.circular(8),
        ),
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                slot.slotName,
                style: Theme.of(context).textTheme.titleSmall,
              ),
              const SizedBox(height: 3),
              Text(slot.timeText, style: Theme.of(context).textTheme.bodySmall),
              if ((slot.price ?? '').isNotEmpty)
                Text(
                  '${slot.price} JOD',
                  style: Theme.of(context).textTheme.bodySmall,
                ),
              Text(
                slot.statusLabel,
                style: TextStyle(color: color, fontWeight: FontWeight.w800),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _DetailsHeader extends StatelessWidget {
  const _DetailsHeader({required this.listing});

  final ListingModel listing;

  @override
  Widget build(BuildContext context) {
    final favorites = Get.find<FavoritesController>();
    final images = listing.imageUrls;

    return SliverAppBar(
      expandedHeight: 300,
      pinned: true,
      backgroundColor: AppTheme.background,
      foregroundColor: AppTheme.textDark,
      actions: [
        Obx(
          () => IconButton.filledTonal(
            onPressed: () => favorites.toggle(listing),
            icon: Icon(
              favorites.isFavorite(listing)
                  ? Icons.favorite
                  : Icons.favorite_border,
            ),
            tooltip: 'المفضلة',
          ),
        ),
        IconButton.filledTonal(
          onPressed: () => Get.find<LaunchService>().openUrl(
            '${Uri.base.origin}/#/details?slug=${listing.slug}',
          ),
          icon: const Icon(Icons.share_outlined),
          tooltip: 'مشاركة',
        ),
        const SizedBox(width: 8),
      ],
      flexibleSpace: FlexibleSpaceBar(
        background: images.isEmpty
            ? Container(
                color: AppTheme.surfaceWarm,
                child: const Icon(
                  Icons.image_outlined,
                  size: 64,
                  color: AppTheme.primaryDark,
                ),
              )
            : PageView.builder(
                itemCount: images.length,
                itemBuilder: (context, index) {
                  return CachedNetworkImage(
                    imageUrl: images[index],
                    fit: BoxFit.cover,
                    errorWidget: (context, url, error) => Container(
                      color: AppTheme.surfaceWarm,
                      child: const Icon(Icons.image_not_supported_outlined),
                    ),
                  );
                },
              ),
      ),
    );
  }
}

class _TitleBlock extends StatelessWidget {
  const _TitleBlock({required this.listing});

  final ListingModel listing;

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(listing.titleAr, style: Theme.of(context).textTheme.headlineSmall),
        const SizedBox(height: 8),
        Wrap(
          spacing: 8,
          runSpacing: 8,
          children: [
            _InfoChip(icon: Icons.payments_outlined, text: listing.priceText),
            if (listing.category != null)
              _InfoChip(
                icon: Icons.category_outlined,
                text: listing.category!.nameAr,
              ),
            if (listing.locationText.isNotEmpty)
              _InfoChip(icon: Icons.place_outlined, text: listing.locationText),
            if (listing.distanceKm != null)
              _InfoChip(
                icon: Icons.near_me_outlined,
                text: '${listing.distanceKm!.toStringAsFixed(1)} كم',
              ),
          ],
        ),
      ],
    );
  }
}

class _ContactActions extends StatelessWidget {
  const _ContactActions({required this.listing});

  final ListingModel listing;

  @override
  Widget build(BuildContext context) {
    final launcher = Get.find<LaunchService>();
    final auth = Get.find<UserAuthController>();
    final isBooking = CategoryPresentation.showsAvailability(listing.category);

    return Column(
      children: [
        SizedBox(
          width: double.infinity,
          child: FilledButton.icon(
            onPressed: () {
              if (!auth.isAuthenticated) {
                Get.toNamed(AppRoutes.login);
                return;
              }
              Get.toNamed(
                AppRoutes.bookingRequest,
                arguments: {'listing': listing},
              );
            },
            icon: Icon(
              isBooking ? Icons.event_available_outlined : Icons.chat_outlined,
            ),
            label: Text(isBooking ? 'طلب حجز' : 'طلب تواصل'),
          ),
        ),
        const SizedBox(height: 10),
        Row(
          children: [
            Expanded(
              child: OutlinedButton.icon(
                onPressed: () =>
                    launcher.openWhatsapp(listing.whatsapp ?? listing.phone),
                icon: const Icon(Icons.chat_outlined),
                label: const Text('واتساب'),
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: OutlinedButton.icon(
                onPressed: () => launcher.callPhone(listing.phone),
                icon: const Icon(Icons.phone_outlined),
                label: const Text('اتصال'),
              ),
            ),
          ],
        ),
      ],
    );
  }
}

class _LocationBox extends StatelessWidget {
  const _LocationBox({required this.listing});

  final ListingModel listing;

  @override
  Widget build(BuildContext context) {
    final latitude = listing.latitude;
    final longitude = listing.longitude;
    if (latitude == null || longitude == null) return const SizedBox.shrink();

    return DecoratedBox(
      decoration: BoxDecoration(
        color: Colors.white,
        border: Border.all(color: AppTheme.border),
        borderRadius: BorderRadius.circular(8),
      ),
      child: Padding(
        padding: const EdgeInsets.all(12),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                const Icon(Icons.map_outlined, color: AppTheme.primaryDark),
                const SizedBox(width: 8),
                Expanded(
                  child: Text(
                    'الموقع',
                    style: Theme.of(context).textTheme.titleMedium,
                  ),
                ),
                OutlinedButton.icon(
                  onPressed: () => Get.find<LaunchService>().openDirections(
                    latitude: latitude,
                    longitude: longitude,
                  ),
                  icon: const Icon(Icons.directions_outlined),
                  label: const Text('الاتجاهات'),
                ),
              ],
            ),
            const SizedBox(height: 10),
            AspectRatio(
              aspectRatio: 16 / 7,
              child: DecoratedBox(
                decoration: BoxDecoration(
                  color: AppTheme.surfaceWarm,
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Center(
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      const Icon(
                        Icons.location_on_outlined,
                        size: 36,
                        color: AppTheme.primaryDark,
                      ),
                      const SizedBox(height: 6),
                      Text(
                        '${latitude.toStringAsFixed(5)}, ${longitude.toStringAsFixed(5)}',
                        style: Theme.of(context).textTheme.titleSmall,
                      ),
                    ],
                  ),
                ),
              ),
            ),
            if ((listing.address ?? listing.locationText).isNotEmpty) ...[
              const SizedBox(height: 8),
              Text(listing.address ?? listing.locationText),
            ],
          ],
        ),
      ),
    );
  }
}

class _AttributesGrid extends StatelessWidget {
  const _AttributesGrid({required this.listing});

  final ListingModel listing;

  @override
  Widget build(BuildContext context) {
    return Wrap(
      spacing: 10,
      runSpacing: 10,
      children: [
        for (final attribute in listing.attributes)
          SizedBox(
            width: MediaQuery.sizeOf(context).width > 620
                ? 220
                : double.infinity,
            child: DecoratedBox(
              decoration: BoxDecoration(
                color: Colors.white,
                border: Border.all(color: AppTheme.border),
                borderRadius: BorderRadius.circular(8),
              ),
              child: Padding(
                padding: const EdgeInsets.all(12),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      attribute.labelAr,
                      style: Theme.of(context).textTheme.bodySmall,
                    ),
                    const SizedBox(height: 4),
                    Text(
                      attribute.displayValue,
                      style: Theme.of(context).textTheme.titleSmall,
                    ),
                  ],
                ),
              ),
            ),
          ),
      ],
    );
  }
}

class _FeaturesWrap extends StatelessWidget {
  const _FeaturesWrap({required this.features});

  final List<ListingFeatureModel> features;

  @override
  Widget build(BuildContext context) {
    return Wrap(
      spacing: 8,
      runSpacing: 8,
      children: [
        for (final feature in features)
          Chip(
            label: Text(
              feature.valueAr == null || feature.valueAr!.isEmpty
                  ? feature.nameAr
                  : '${feature.nameAr}: ${feature.valueAr}',
            ),
          ),
      ],
    );
  }
}

class _InfoChip extends StatelessWidget {
  const _InfoChip({required this.icon, required this.text});

  final IconData icon;
  final String text;

  @override
  Widget build(BuildContext context) {
    return Chip(
      avatar: Icon(icon, size: 18),
      label: Text(text),
      visualDensity: VisualDensity.compact,
    );
  }
}

class _SectionTitle extends StatelessWidget {
  const _SectionTitle(this.text);

  final String text;

  @override
  Widget build(BuildContext context) {
    return Text(text, style: Theme.of(context).textTheme.titleLarge);
  }
}
