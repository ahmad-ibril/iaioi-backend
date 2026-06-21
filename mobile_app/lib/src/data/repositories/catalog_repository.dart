import '../../core/network/api_client.dart';
import '../models/availability_slot_model.dart';
import '../models/calendar_date_model.dart';
import '../models/category_model.dart';
import '../models/listing_model.dart';

class CatalogRepository {
  CatalogRepository(this._apiClient);

  final ApiClient _apiClient;

  Future<List<CategoryModel>> fetchCategories() async {
    final data = await _apiClient.getMap('/categories');
    return (data['data'] as List? ?? [])
        .whereType<Map<String, dynamic>>()
        .map(CategoryModel.fromJson)
        .toList();
  }

  Future<List<ListingModel>> fetchListings({
    String? categorySlug,
    Map<String, dynamic>? query,
  }) async {
    final effectiveQuery = Map<String, dynamic>.from(query ?? const {});
    final queryHasCategory = effectiveQuery.keys.any(
      {
        'category',
        'category_slug',
        'category_slugs',
        'category_ids',
        'group_key',
      }.contains,
    );
    final path = categorySlug == null || queryHasCategory
        ? '/listings'
        : '/categories/$categorySlug/listings';

    final data = await _apiClient.getMap(path, query: effectiveQuery);
    return (data['data'] as List? ?? [])
        .whereType<Map<String, dynamic>>()
        .map(ListingModel.fromJson)
        .toList();
  }

  Future<ListingModel> fetchListingDetails(String slug) async {
    final data = await _apiClient.getMap('/listings/$slug');
    return ListingModel.fromJson(
      data['data'] is Map<String, dynamic> ? data['data'] : data,
    );
  }

  Future<List<AvailabilitySlotModel>> fetchAvailability(String slug) async {
    final data = await _apiClient.getMap('/listings/$slug/availability');
    return (data['data'] as List? ?? [])
        .whereType<Map<String, dynamic>>()
        .map(AvailabilitySlotModel.fromJson)
        .toList();
  }

  Future<List<CalendarDateModel>> fetchCalendarAvailability(String slug) async {
    final data = await _apiClient.getMap('/listings/$slug/availability');
    return (data['data'] as List? ?? [])
        .whereType<Map<String, dynamic>>()
        .map(CalendarDateModel.fromJson)
        .toList();
  }
}
