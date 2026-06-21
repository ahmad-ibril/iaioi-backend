import '../../core/network/api_client.dart';
import '../models/booking_request_model.dart';

class BookingRepository {
  BookingRepository(this._apiClient);

  final ApiClient _apiClient;

  Future<List<BookingRequestModel>> fetchBookingRequests() async {
    final data = await _apiClient.getMap('/my-booking-requests');
    return (data['data'] as List? ?? [])
        .whereType<Map<String, dynamic>>()
        .map(BookingRequestModel.fromJson)
        .toList();
  }

  Future<OwnerDashboardModel> fetchOwnerDashboard() async {
    final data = await _apiClient.getMap('/owner/dashboard');
    return OwnerDashboardModel.fromJson(
      data['data'] is Map<String, dynamic> ? data['data'] : data,
    );
  }

  Future<List<BookingRequestModel>> fetchOwnerBookingRequests() async {
    final data = await _apiClient.getMap(
      '/owner/booking-requests',
      query: {'per_page': 100},
    );
    return (data['data'] as List? ?? [])
        .whereType<Map<String, dynamic>>()
        .map(BookingRequestModel.fromJson)
        .toList();
  }

  Future<BookingRequestModel> createBookingRequest({
    required int listingId,
    int? availabilitySlotId,
    DateTime? dateFrom,
    DateTime? dateTo,
    int quantity = 1,
    String? contactName,
    String? contactPhone,
    String? notes,
  }) async {
    final payload = <String, dynamic>{
      'quantity': quantity,
      if (dateFrom != null) 'date_from': _dateText(dateFrom),
      if (dateTo != null) 'date_to': _dateText(dateTo),
      if ((contactName ?? '').trim().isNotEmpty)
        'contact_name': contactName!.trim(),
      if ((contactPhone ?? '').trim().isNotEmpty)
        'contact_phone': contactPhone!.trim(),
      if ((notes ?? '').trim().isNotEmpty) 'notes': notes!.trim(),
    };
    if (availabilitySlotId != null) {
      payload['availability_slot_id'] = availabilitySlotId;
    }

    final data = await _apiClient.postMap(
      '/listings/$listingId/booking-request',
      data: payload,
    );

    return BookingRequestModel.fromJson(
      data['data'] is Map<String, dynamic> ? data['data'] : data,
    );
  }

  Future<void> cancelBookingRequest(int id) async {
    await _apiClient.patchMap('/booking-requests/$id/cancel');
  }

  Future<BookingRequestModel> updateOwnerBookingRequest({
    required int id,
    required String status,
    String? adminNotes,
  }) async {
    final normalized = switch (status) {
      'confirmed' => 'accepted',
      'in_review' || 'new' => 'pending',
      _ => status,
    };
    final path = normalized == 'accepted'
        ? '/booking-requests/$id/accept'
        : normalized == 'rejected'
        ? '/booking-requests/$id/reject'
        : '/owner/booking-requests/$id';
    final methodData = {
      'status': normalized,
      if ((adminNotes ?? '').trim().isNotEmpty)
        'admin_notes': adminNotes!.trim(),
    };
    final data = normalized == 'accepted' || normalized == 'rejected'
        ? await _apiClient.putMap(path, data: methodData)
        : await _apiClient.patchMap(path, data: methodData);

    return BookingRequestModel.fromJson(
      data['data'] is Map<String, dynamic> ? data['data'] : data,
    );
  }
}

class OwnerDashboardModel {
  const OwnerDashboardModel({
    required this.listingsTotal,
    required this.listingsActive,
    required this.listingsPending,
    required this.bookingsTotal,
    required this.bookingsNew,
    required this.bookingsInReview,
    required this.bookingsConfirmed,
  });

  final int listingsTotal;
  final int listingsActive;
  final int listingsPending;
  final int bookingsTotal;
  final int bookingsNew;
  final int bookingsInReview;
  final int bookingsConfirmed;

  factory OwnerDashboardModel.fromJson(Map<String, dynamic> json) {
    final listings = json['listings'] is Map
        ? Map<String, dynamic>.from(json['listings'])
        : const <String, dynamic>{};
    final bookings = json['bookings'] is Map
        ? Map<String, dynamic>.from(json['bookings'])
        : const <String, dynamic>{};

    return OwnerDashboardModel(
      listingsTotal: int.tryParse('${listings['total'] ?? 0}') ?? 0,
      listingsActive: int.tryParse('${listings['active'] ?? 0}') ?? 0,
      listingsPending: int.tryParse('${listings['pending'] ?? 0}') ?? 0,
      bookingsTotal: int.tryParse('${bookings['total'] ?? 0}') ?? 0,
      bookingsNew: int.tryParse('${bookings['new'] ?? 0}') ?? 0,
      bookingsInReview: int.tryParse('${bookings['in_review'] ?? 0}') ?? 0,
      bookingsConfirmed: int.tryParse('${bookings['confirmed'] ?? 0}') ?? 0,
    );
  }
}

String _dateText(DateTime date) {
  final month = date.month.toString().padLeft(2, '0');
  final day = date.day.toString().padLeft(2, '0');
  return '${date.year}-$month-$day';
}
