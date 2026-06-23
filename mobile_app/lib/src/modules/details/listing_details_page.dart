import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:table_calendar/table_calendar.dart';

import '../../core/config/app_theme.dart';
import '../../core/services/launch_service.dart';
import '../../core/utils/category_presentation.dart';
import '../../data/models/availability_slot_model.dart';
import '../../data/models/listing_model.dart';
import '../../data/repositories/catalog_repository.dart';
import '../../routes/app_routes.dart';
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
  late DateTime focusedDate;
  AvailabilitySlotModel? selectedSlot;
  _AvailabilityDayFilter filter = _AvailabilityDayFilter.all;

  @override
  void initState() {
    super.initState();
    selectedDate = _firstAvailableDate() ?? DateUtils.dateOnly(DateTime.now());
    focusedDate = selectedDate!;
    selectedSlot = _firstAvailableSlotForDate(selectedDate);
  }

  @override
  void didUpdateWidget(covariant _BookingPickerSection oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (oldWidget.slots != widget.slots) {
      final current = selectedDate;
      selectedDate = current ?? _firstAvailableDate();
      focusedDate = selectedDate ?? focusedDate;
      selectedSlot = _firstAvailableSlotForDate(selectedDate);
    }
  }

  @override
  Widget build(BuildContext context) {
    final booking = Get.find<BookingController>();
    final selectedDayState = _stateForDate(selectedDate);
    final nearestSlot = _nearestAvailableSlot();
    final nearestDate = nearestSlot == null
        ? null
        : DateUtils.dateOnly(nearestSlot.date);

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
            const _AvailabilityLegend(),
            const SizedBox(height: 12),
            if (widget.slots.isEmpty) ...[
              const _NoAvailabilityNotice(),
              const SizedBox(height: 12),
            ],
            _NearestAvailabilityBar(
              nearestDate: nearestDate,
              isSubmitting: booking.isSubmitting,
              onBook: nearestSlot == null
                  ? null
                  : () async {
                      setState(() {
                        selectedDate = DateUtils.dateOnly(nearestSlot.date);
                        focusedDate = selectedDate!;
                        selectedSlot = nearestSlot;
                      });
                      await _submit(booking);
                    },
            ),
            const SizedBox(height: 12),
            LayoutBuilder(
              builder: (context, constraints) {
                final narrow = constraints.maxWidth < 460;
                final title = Text(
                  'اختر يوماً لعرض تفاصيل التوفر والحجز.',
                  style: Theme.of(context).textTheme.bodySmall,
                );
                final filterButton = _AvailabilityFilterButton(
                  value: filter,
                  onChanged: _changeFilter,
                );

                if (narrow) {
                  return Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [title, const SizedBox(height: 8), filterButton],
                  );
                }

                return Row(
                  children: [
                    Expanded(child: title),
                    const SizedBox(width: 8),
                    filterButton,
                  ],
                );
              },
            ),
            const SizedBox(height: 12),
            _AvailabilityCalendarView(
              focusedDate: focusedDate,
              selectedDate: selectedDate,
              filter: filter,
              stateForDate: _stateForDate,
              onPageChanged: (date) {
                setState(() => focusedDate = DateUtils.dateOnly(date));
              },
              onDaySelected: (day, focusedDay) {
                setState(() {
                  selectedDate = DateUtils.dateOnly(day);
                  focusedDate = DateUtils.dateOnly(focusedDay);
                  selectedSlot = _firstAvailableSlotForDate(selectedDate);
                });
              },
            ),
            const SizedBox(height: 12),
            _SelectedDayDetails(
              state: selectedDayState,
              selectedSlot: selectedSlot,
              isSubmitting: booking.isSubmitting,
              onBook: selectedSlot == null ? null : () => _submit(booking),
            ),
          ],
        ),
      ),
    );
  }

  DateTime? _firstAvailableDate() {
    final slot = _nearestAvailableSlot();
    return slot == null ? null : DateUtils.dateOnly(slot.date);
  }

  AvailabilitySlotModel? _nearestAvailableSlot() {
    final today = DateUtils.dateOnly(DateTime.now());
    final futureSlots =
        widget.slots
            .where((slot) => slot.isAvailable)
            .where((slot) => !DateUtils.dateOnly(slot.date).isBefore(today))
            .toList()
          ..sort((a, b) => a.date.compareTo(b.date));
    if (futureSlots.isNotEmpty) return futureSlots.first;

    final available = widget.slots.where((slot) => slot.isAvailable).toList()
      ..sort((a, b) => a.date.compareTo(b.date));
    return available.isEmpty ? null : available.first;
  }

  AvailabilitySlotModel? _firstAvailableSlotForDate(DateTime? date) {
    if (date == null) return null;
    final slots =
        widget.slots
            .where((slot) => DateUtils.isSameDay(slot.date, date))
            .where((slot) => slot.isAvailable)
            .toList()
          ..sort((a, b) {
            final timeCompare = (a.startTime ?? '').compareTo(
              b.startTime ?? '',
            );
            return timeCompare != 0 ? timeCompare : a.id.compareTo(b.id);
          });
    return slots.isEmpty ? null : slots.first;
  }

  _DayAvailabilityState _stateForDate(DateTime? date) {
    final day = DateUtils.dateOnly(date ?? DateTime.now());
    final slots =
        widget.slots
            .where((slot) => DateUtils.isSameDay(slot.date, day))
            .toList()
          ..sort((a, b) {
            final timeCompare = (a.startTime ?? '').compareTo(
              b.startTime ?? '',
            );
            return timeCompare != 0 ? timeCompare : a.id.compareTo(b.id);
          });

    return _DayAvailabilityState(date: day, slots: slots);
  }

  void _changeFilter(_AvailabilityDayFilter value) {
    setState(() {
      filter = value;
      if (_matchesFilter(value, _stateForDate(selectedDate).status)) return;

      selectedDate = _firstDateForFilter(value);
      focusedDate = selectedDate ?? focusedDate;
      selectedSlot = _firstAvailableSlotForDate(selectedDate);
    });
  }

  DateTime? _firstDateForFilter(_AvailabilityDayFilter value) {
    if (value == _AvailabilityDayFilter.all) {
      return selectedDate ?? _firstAvailableDate();
    }

    final dates = {
      for (final slot in widget.slots) DateUtils.dateOnly(slot.date),
    }.toList()..sort();

    for (final date in dates) {
      if (_matchesFilter(value, _stateForDate(date).status)) return date;
    }
    return null;
  }

  bool _matchesFilter(
    _AvailabilityDayFilter value,
    _DayAvailabilityStatus status,
  ) {
    return switch (value) {
      _AvailabilityDayFilter.all => true,
      _AvailabilityDayFilter.available => status.canBook,
      _AvailabilityDayFilter.booked =>
        status == _DayAvailabilityStatus.fullyBooked,
    };
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

enum _AvailabilityDayFilter {
  all('كل الأيام'),
  available('المتاحة فقط'),
  booked('المحجوزة فقط');

  const _AvailabilityDayFilter(this.label);

  final String label;
}

enum _DayAvailabilityStatus {
  fullyAvailable,
  partiallyAvailable,
  fullyBooked,
  noData;

  bool get canBook {
    return this == _DayAvailabilityStatus.fullyAvailable ||
        this == _DayAvailabilityStatus.partiallyAvailable;
  }
}

class _DayAvailabilityState {
  const _DayAvailabilityState({required this.date, required this.slots});

  final DateTime date;
  final List<AvailabilitySlotModel> slots;

  List<AvailabilitySlotModel> get availableSlots {
    return slots.where((slot) => slot.isAvailable).toList();
  }

  _DayAvailabilityStatus get status {
    if (slots.isEmpty) return _DayAvailabilityStatus.noData;
    if (availableSlots.length == slots.length) {
      return _DayAvailabilityStatus.fullyAvailable;
    }
    if (availableSlots.isNotEmpty) {
      return _DayAvailabilityStatus.partiallyAvailable;
    }
    return _DayAvailabilityStatus.fullyBooked;
  }

  String get label {
    return switch (status) {
      _DayAvailabilityStatus.fullyAvailable => 'متاح بالكامل',
      _DayAvailabilityStatus.partiallyAvailable => 'متاح جزئياً',
      _DayAvailabilityStatus.fullyBooked => 'محجوز بالكامل',
      _DayAvailabilityStatus.noData => 'لا توجد بيانات',
    };
  }

  Color get color {
    return switch (status) {
      _DayAvailabilityStatus.fullyAvailable => AppTheme.green,
      _DayAvailabilityStatus.partiallyAvailable => AppTheme.warning,
      _DayAvailabilityStatus.fullyBooked => AppTheme.danger,
      _DayAvailabilityStatus.noData => const Color(0xFF98A2B3),
    };
  }

  Color get backgroundColor {
    return switch (status) {
      _DayAvailabilityStatus.fullyAvailable => const Color(0xFFEAF8F2),
      _DayAvailabilityStatus.partiallyAvailable => const Color(0xFFFFF7DF),
      _DayAvailabilityStatus.fullyBooked => const Color(0xFFFFE8E8),
      _DayAvailabilityStatus.noData => const Color(0xFFF2F4F7),
    };
  }

  String? get priceText {
    final prices = <String>{
      for (final slot in slots)
        if ((slot.price ?? '').trim().isNotEmpty) slot.price!.trim(),
    }.toList();
    if (prices.isEmpty) return null;
    return prices.join(' / ');
  }
}

class _AvailabilityLegend extends StatelessWidget {
  const _AvailabilityLegend();

  @override
  Widget build(BuildContext context) {
    return const Wrap(
      spacing: 8,
      runSpacing: 8,
      children: [
        _LegendItem(color: AppTheme.green, label: 'متاح بالكامل'),
        _LegendItem(color: AppTheme.warning, label: 'متاح جزئياً'),
        _LegendItem(color: AppTheme.danger, label: 'محجوز بالكامل'),
        _LegendItem(color: Color(0xFF98A2B3), label: 'لا توجد بيانات'),
      ],
    );
  }
}

class _LegendItem extends StatelessWidget {
  const _LegendItem({required this.color, required this.label});

  final Color color;
  final String label;

  @override
  Widget build(BuildContext context) {
    return DecoratedBox(
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.1),
        border: Border.all(color: color.withValues(alpha: 0.35)),
        borderRadius: BorderRadius.circular(8),
      ),
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 7),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            DecoratedBox(
              decoration: BoxDecoration(color: color, shape: BoxShape.circle),
              child: const SizedBox(width: 9, height: 9),
            ),
            const SizedBox(width: 6),
            Text(label, style: Theme.of(context).textTheme.bodySmall),
          ],
        ),
      ),
    );
  }
}

class _NoAvailabilityNotice extends StatelessWidget {
  const _NoAvailabilityNotice();

  @override
  Widget build(BuildContext context) {
    return DecoratedBox(
      decoration: BoxDecoration(
        color: const Color(0xFFF2F4F7),
        border: Border.all(color: AppTheme.border),
        borderRadius: BorderRadius.circular(8),
      ),
      child: Padding(
        padding: const EdgeInsets.all(12),
        child: Row(
          children: [
            const Icon(Icons.info_outline, color: AppTheme.textMuted),
            const SizedBox(width: 8),
            Expanded(
              child: Text(
                'لم يقم صاحب الإعلان بإضافة مواعيد متاحة بعد.',
                style: Theme.of(context).textTheme.bodySmall,
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _NearestAvailabilityBar extends StatelessWidget {
  const _NearestAvailabilityBar({
    required this.nearestDate,
    required this.isSubmitting,
    required this.onBook,
  });

  final DateTime? nearestDate;
  final RxBool isSubmitting;
  final Future<void> Function()? onBook;

  @override
  Widget build(BuildContext context) {
    final hasNearest = nearestDate != null;
    return DecoratedBox(
      decoration: BoxDecoration(
        color: AppTheme.secondarySoft,
        border: Border.all(color: AppTheme.secondary.withValues(alpha: 0.24)),
        borderRadius: BorderRadius.circular(8),
      ),
      child: Padding(
        padding: const EdgeInsets.all(12),
        child: LayoutBuilder(
          builder: (context, constraints) {
            final compact = constraints.maxWidth < 520;
            final title = hasNearest
                ? 'أقرب موعد متاح: ${_formatArabicDate(nearestDate!)}'
                : 'لا توجد مواعيد متاحة حالياً';
            final button = Obx(
              () => FilledButton.icon(
                style: FilledButton.styleFrom(
                  backgroundColor: AppTheme.green,
                  foregroundColor: Colors.white,
                ),
                onPressed: !hasNearest || isSubmitting.value ? null : onBook,
                icon: isSubmitting.value
                    ? const SizedBox(
                        width: 18,
                        height: 18,
                        child: CircularProgressIndicator(strokeWidth: 2),
                      )
                    : const Icon(Icons.event_available_outlined),
                label: const Text('احجز أقرب موعد'),
              ),
            );

            if (compact) {
              return Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  Text(title, style: Theme.of(context).textTheme.titleSmall),
                  const SizedBox(height: 10),
                  button,
                ],
              );
            }

            return Row(
              children: [
                Expanded(
                  child: Text(
                    title,
                    style: Theme.of(context).textTheme.titleSmall,
                  ),
                ),
                const SizedBox(width: 10),
                button,
              ],
            );
          },
        ),
      ),
    );
  }
}

class _AvailabilityFilterButton extends StatelessWidget {
  const _AvailabilityFilterButton({
    required this.value,
    required this.onChanged,
  });

  final _AvailabilityDayFilter value;
  final ValueChanged<_AvailabilityDayFilter> onChanged;

  @override
  Widget build(BuildContext context) {
    return PopupMenuButton<_AvailabilityDayFilter>(
      tooltip: 'فلترة الأيام',
      onSelected: onChanged,
      itemBuilder: (context) => [
        for (final option in _AvailabilityDayFilter.values)
          PopupMenuItem(value: option, child: Text(option.label)),
      ],
      child: ConstrainedBox(
        constraints: const BoxConstraints(maxWidth: 220),
        child: DecoratedBox(
          decoration: BoxDecoration(
            color: Colors.white,
            border: Border.all(color: AppTheme.border),
            borderRadius: BorderRadius.circular(8),
          ),
          child: Padding(
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
            child: Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                const Icon(Icons.filter_list_outlined, size: 18),
                const SizedBox(width: 6),
                Flexible(
                  child: Text(
                    'فلترة الأيام: ${value.label}',
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(fontWeight: FontWeight.w800),
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

class _AvailabilityCalendarView extends StatelessWidget {
  const _AvailabilityCalendarView({
    required this.focusedDate,
    required this.selectedDate,
    required this.filter,
    required this.stateForDate,
    required this.onDaySelected,
    required this.onPageChanged,
  });

  final DateTime focusedDate;
  final DateTime? selectedDate;
  final _AvailabilityDayFilter filter;
  final _DayAvailabilityState Function(DateTime date) stateForDate;
  final void Function(DateTime selectedDay, DateTime focusedDay) onDaySelected;
  final ValueChanged<DateTime> onPageChanged;

  @override
  Widget build(BuildContext context) {
    return TableCalendar<void>(
      locale: 'ar',
      firstDay: DateTime.now().subtract(const Duration(days: 365)),
      lastDay: DateTime.now().add(const Duration(days: 730)),
      focusedDay: focusedDate,
      rowHeight: 64,
      daysOfWeekHeight: 28,
      startingDayOfWeek: StartingDayOfWeek.saturday,
      selectedDayPredicate: (day) {
        return selectedDate != null && DateUtils.isSameDay(selectedDate, day);
      },
      enabledDayPredicate: (day) => _matchesFilter(stateForDate(day).status),
      onDaySelected: onDaySelected,
      onPageChanged: onPageChanged,
      headerStyle: const HeaderStyle(
        formatButtonVisible: false,
        titleCentered: true,
        leftChevronIcon: Icon(Icons.chevron_left),
        rightChevronIcon: Icon(Icons.chevron_right),
      ),
      daysOfWeekStyle: const DaysOfWeekStyle(
        weekdayStyle: TextStyle(
          color: AppTheme.textMuted,
          fontWeight: FontWeight.w700,
        ),
        weekendStyle: TextStyle(
          color: AppTheme.textMuted,
          fontWeight: FontWeight.w700,
        ),
      ),
      calendarBuilders: CalendarBuilders(
        defaultBuilder: (context, day, focusedDay) {
          return _AvailabilityDayCell(state: stateForDate(day));
        },
        todayBuilder: (context, day, focusedDay) {
          return _AvailabilityDayCell(state: stateForDate(day), isToday: true);
        },
        selectedBuilder: (context, day, focusedDay) {
          return _AvailabilityDayCell(
            state: stateForDate(day),
            isSelected: true,
          );
        },
        disabledBuilder: (context, day, focusedDay) {
          return _AvailabilityDayCell(
            state: stateForDate(day),
            isDisabled: true,
          );
        },
        outsideBuilder: (context, day, focusedDay) {
          final state = stateForDate(day);
          return _AvailabilityDayCell(
            state: state,
            isOutside: true,
            isDisabled: !_matchesFilter(state.status),
          );
        },
      ),
    );
  }

  bool _matchesFilter(_DayAvailabilityStatus status) {
    return switch (filter) {
      _AvailabilityDayFilter.all => true,
      _AvailabilityDayFilter.available => status.canBook,
      _AvailabilityDayFilter.booked =>
        status == _DayAvailabilityStatus.fullyBooked,
    };
  }
}

class _AvailabilityDayCell extends StatelessWidget {
  const _AvailabilityDayCell({
    required this.state,
    this.isToday = false,
    this.isSelected = false,
    this.isOutside = false,
    this.isDisabled = false,
  });

  final _DayAvailabilityState state;
  final bool isToday;
  final bool isSelected;
  final bool isOutside;
  final bool isDisabled;

  @override
  Widget build(BuildContext context) {
    final opacity = isDisabled || isOutside ? 0.42 : 1.0;
    final borderColor = isSelected
        ? AppTheme.green
        : isToday
        ? AppTheme.primaryDark
        : state.color.withValues(alpha: 0.35);

    return Opacity(
      opacity: opacity,
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 2, vertical: 3),
        child: DecoratedBox(
          decoration: BoxDecoration(
            color: state.backgroundColor,
            border: Border.all(color: borderColor, width: isSelected ? 2 : 1),
            borderRadius: BorderRadius.circular(12),
          ),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Text(
                '${state.date.day}',
                style: TextStyle(
                  color: AppTheme.textDark,
                  fontWeight: isSelected ? FontWeight.w900 : FontWeight.w800,
                  fontSize: 15,
                ),
              ),
              const SizedBox(height: 6),
              DecoratedBox(
                decoration: BoxDecoration(
                  color: state.color,
                  shape: BoxShape.circle,
                ),
                child: const SizedBox(width: 7, height: 7),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _SelectedDayDetails extends StatelessWidget {
  const _SelectedDayDetails({
    required this.state,
    required this.selectedSlot,
    required this.isSubmitting,
    required this.onBook,
  });

  final _DayAvailabilityState state;
  final AvailabilitySlotModel? selectedSlot;
  final RxBool isSubmitting;
  final Future<void> Function()? onBook;

  @override
  Widget build(BuildContext context) {
    final availableSlots = state.availableSlots;
    final canBook = state.status.canBook && selectedSlot != null;

    return DecoratedBox(
      decoration: BoxDecoration(
        color: state.backgroundColor.withValues(alpha: 0.72),
        border: Border.all(color: state.color.withValues(alpha: 0.32)),
        borderRadius: BorderRadius.circular(8),
      ),
      child: Padding(
        padding: const EdgeInsets.all(12),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Expanded(
                  child: Text(
                    _formatArabicDate(state.date),
                    style: Theme.of(context).textTheme.titleMedium,
                  ),
                ),
                _StatusBadge(state: state),
              ],
            ),
            const SizedBox(height: 10),
            _DetailLine(label: 'حالة اليوم', value: state.label),
            if (state.priceText != null)
              _DetailLine(label: 'السعر', value: '${state.priceText} JOD'),
            if (availableSlots.isNotEmpty) ...[
              const SizedBox(height: 8),
              Text(
                'الفترات المتاحة',
                style: Theme.of(context).textTheme.titleSmall,
              ),
              const SizedBox(height: 6),
              Wrap(
                spacing: 8,
                runSpacing: 8,
                children: [
                  for (final slot in availableSlots)
                    _SlotPeriodChip(slot: slot),
                ],
              ),
            ],
            if (state.status == _DayAvailabilityStatus.noData) ...[
              const SizedBox(height: 8),
              Text(
                'لم يقم صاحب الإعلان بإضافة مواعيد متاحة بعد.',
                style: Theme.of(context).textTheme.bodySmall,
              ),
            ],
            if (state.status == _DayAvailabilityStatus.fullyBooked) ...[
              const SizedBox(height: 8),
              Text(
                'هذا اليوم محجوز بالكامل',
                style: Theme.of(
                  context,
                ).textTheme.titleSmall?.copyWith(color: AppTheme.danger),
              ),
            ],
            if (state.status.canBook) ...[
              const SizedBox(height: 12),
              Obx(
                () => SizedBox(
                  width: double.infinity,
                  child: FilledButton.icon(
                    style: FilledButton.styleFrom(
                      backgroundColor: AppTheme.green,
                      foregroundColor: Colors.white,
                    ),
                    onPressed: canBook && !isSubmitting.value ? onBook : null,
                    icon: isSubmitting.value
                        ? const SizedBox(
                            width: 18,
                            height: 18,
                            child: CircularProgressIndicator(strokeWidth: 2),
                          )
                        : const Icon(Icons.event_available_outlined),
                    label: const Text('احجز الآن'),
                  ),
                ),
              ),
            ],
          ],
        ),
      ),
    );
  }
}

class _StatusBadge extends StatelessWidget {
  const _StatusBadge({required this.state});

  final _DayAvailabilityState state;

  @override
  Widget build(BuildContext context) {
    return DecoratedBox(
      decoration: BoxDecoration(
        color: state.color.withValues(alpha: 0.12),
        border: Border.all(color: state.color.withValues(alpha: 0.4)),
        borderRadius: BorderRadius.circular(8),
      ),
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 9, vertical: 6),
        child: Text(
          state.label,
          style: TextStyle(color: state.color, fontWeight: FontWeight.w800),
        ),
      ),
    );
  }
}

class _DetailLine extends StatelessWidget {
  const _DetailLine({required this.label, required this.value});

  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 4),
      child: Row(
        children: [
          Text(
            '$label: ',
            style: Theme.of(context).textTheme.bodySmall?.copyWith(
              fontWeight: FontWeight.w800,
              color: AppTheme.textDark,
            ),
          ),
          Expanded(
            child: Text(value, style: Theme.of(context).textTheme.bodySmall),
          ),
        ],
      ),
    );
  }
}

class _SlotPeriodChip extends StatelessWidget {
  const _SlotPeriodChip({required this.slot});

  final AvailabilitySlotModel slot;

  @override
  Widget build(BuildContext context) {
    return DecoratedBox(
      decoration: BoxDecoration(
        color: Colors.white,
        border: Border.all(color: AppTheme.green.withValues(alpha: 0.28)),
        borderRadius: BorderRadius.circular(8),
      ),
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Icon(
              Icons.schedule_outlined,
              size: 16,
              color: AppTheme.green,
            ),
            const SizedBox(width: 6),
            Text(_slotPeriodLabel(slot)),
          ],
        ),
      ),
    );
  }
}

String _slotPeriodLabel(AvailabilitySlotModel slot) {
  if ((slot.startTime ?? '').isEmpty && (slot.endTime ?? '').isEmpty) {
    return 'يوم كامل';
  }
  final text = slot.timeText.trim();
  return text.isEmpty ? 'يوم كامل' : text;
}

String _formatArabicDate(DateTime date) {
  const dayNames = {
    DateTime.monday: 'الاثنين',
    DateTime.tuesday: 'الثلاثاء',
    DateTime.wednesday: 'الأربعاء',
    DateTime.thursday: 'الخميس',
    DateTime.friday: 'الجمعة',
    DateTime.saturday: 'السبت',
    DateTime.sunday: 'الأحد',
  };
  const monthNames = {
    1: 'يناير',
    2: 'فبراير',
    3: 'مارس',
    4: 'أبريل',
    5: 'مايو',
    6: 'يونيو',
    7: 'يوليو',
    8: 'أغسطس',
    9: 'سبتمبر',
    10: 'أكتوبر',
    11: 'نوفمبر',
    12: 'ديسمبر',
  };

  final dayName = dayNames[date.weekday] ?? '';
  final monthName = monthNames[date.month] ?? '${date.month}';
  return '$dayName ${date.day} $monthName ${date.year}';
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
