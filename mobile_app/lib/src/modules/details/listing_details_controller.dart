import 'package:get/get.dart';

import '../../core/utils/category_presentation.dart';
import '../../data/models/availability_slot_model.dart';
import '../../data/models/calendar_date_model.dart';
import '../../data/models/listing_model.dart';
import '../../data/repositories/catalog_repository.dart';

class ListingDetailsController extends GetxController {
  ListingDetailsController(
    this._repository,
    this.slug, {
    ListingModel? initialListing,
  }) {
    listing.value = initialListing;
  }

  final CatalogRepository _repository;
  final String slug;

  final listing = Rxn<ListingModel>();
  final availability = <AvailabilitySlotModel>[].obs;
  final isLoading = false.obs;
  final error = RxnString();

  @override
  void onInit() {
    super.onInit();
    loadDetails();
  }

  Future<void> loadDetails() async {
    isLoading.value = true;
    error.value = null;
    try {
      final details = await _repository.fetchListingDetails(slug);
      listing.value = details;
      if (CategoryPresentation.showsAvailability(details.category)) {
        availability.assignAll(details.availabilitySlots);
        if (availability.isEmpty) {
          availability.assignAll(await _repository.fetchAvailability(slug));
        }
        if (availability.isEmpty && details.calendarDates.isNotEmpty) {
          availability.assignAll(details.calendarDates.map(_slotFromDate));
        }
      } else {
        availability.clear();
      }
    } catch (_) {
      error.value = 'تعذر تحميل تفاصيل الخدمة.';
    } finally {
      isLoading.value = false;
    }
  }
}

AvailabilitySlotModel _slotFromDate(CalendarDateModel date) {
  return AvailabilitySlotModel(
    id: 0,
    listingId: 0,
    date: date.date,
    slotName: 'يوم كامل',
    status: switch (date.status) {
      'booked' => 'reserved',
      'blocked' => 'unavailable',
      _ => 'available',
    },
    price: date.priceOverride,
  );
}
