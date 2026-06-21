import '../../core/network/api_client.dart';

class AdminRepository {
  AdminRepository(this._apiClient);

  final ApiClient _apiClient;

  Future<Map<String, dynamic>> dashboard() async {
    final data = await _apiClient.getMap('/admin/dashboard');
    return _map(data['data']);
  }

  Future<List<Map<String, dynamic>>> listings() => _list('/admin/listings');
  Future<List<Map<String, dynamic>>> categories() => _list('/admin/categories');
  Future<List<Map<String, dynamic>>> users() => _list('/admin/users');
  Future<List<Map<String, dynamic>>> bookings() =>
      _list('/admin/booking-requests');
  Future<List<Map<String, dynamic>>> availabilitySlots() =>
      _list('/admin/availability-slots');
  Future<List<Map<String, dynamic>>> cities() => _list('/admin/cities');
  Future<List<Map<String, dynamic>>> areas() => _list('/admin/city-areas');
  Future<List<Map<String, dynamic>>> banners() => _list('/admin/banners');

  Future<Map<String, dynamic>> settings() async {
    final data = await _apiClient.getMap('/admin/settings');
    return _map(data['data']);
  }

  Future<void> updateSettings(Map<String, dynamic> settings) async {
    await _apiClient.putMap('/admin/settings', data: {'settings': settings});
  }

  Future<void> createListing(Map<String, dynamic> payload) async {
    await _apiClient.postMap('/admin/listings', data: payload);
  }

  Future<void> updateListing(int id, Map<String, dynamic> payload) async {
    await _apiClient.patchMap('/admin/listings/$id', data: payload);
  }

  Future<void> updateListingStatus(int id, Map<String, dynamic> payload) async {
    await _apiClient.patchMap('/admin/listings/$id/status', data: payload);
  }

  Future<void> deleteListing(int id) =>
      _apiClient.delete('/admin/listings/$id');

  Future<void> createCategory(Map<String, dynamic> payload) async {
    await _apiClient.postMap('/admin/categories', data: payload);
  }

  Future<void> updateCategory(int id, Map<String, dynamic> payload) async {
    await _apiClient.patchMap('/admin/categories/$id', data: payload);
  }

  Future<void> deleteCategory(int id) =>
      _apiClient.delete('/admin/categories/$id');

  Future<void> updateUser(int id, Map<String, dynamic> payload) async {
    await _apiClient.patchMap('/admin/users/$id', data: payload);
  }

  Future<void> deleteUser(int id) => _apiClient.delete('/admin/users/$id');

  Future<void> updateBooking(int id, Map<String, dynamic> payload) async {
    await _apiClient.putMap('/admin/booking-requests/$id', data: payload);
  }

  Future<void> acceptBooking(int id) async {
    await _apiClient.putMap('/admin/booking-requests/$id/accept');
  }

  Future<void> rejectBooking(int id) async {
    await _apiClient.putMap('/admin/booking-requests/$id/reject');
  }

  Future<void> createSlot(Map<String, dynamic> payload) async {
    await _apiClient.postMap('/admin/availability-slots', data: payload);
  }

  Future<void> updateSlot(int id, Map<String, dynamic> payload) async {
    await _apiClient.patchMap('/admin/availability-slots/$id', data: payload);
  }

  Future<void> deleteSlot(int id) =>
      _apiClient.delete('/admin/availability-slots/$id');

  Future<void> createCity(Map<String, dynamic> payload) async {
    await _apiClient.postMap('/admin/cities', data: payload);
  }

  Future<void> updateCity(int id, Map<String, dynamic> payload) async {
    await _apiClient.putMap('/admin/cities/$id', data: payload);
  }

  Future<void> deleteCity(int id) => _apiClient.delete('/admin/cities/$id');

  Future<void> createArea(Map<String, dynamic> payload) async {
    await _apiClient.postMap('/admin/city-areas', data: payload);
  }

  Future<void> updateArea(int id, Map<String, dynamic> payload) async {
    await _apiClient.putMap('/admin/city-areas/$id', data: payload);
  }

  Future<void> deleteArea(int id) => _apiClient.delete('/admin/city-areas/$id');

  Future<void> createBanner(Map<String, dynamic> payload) async {
    await _apiClient.postMap('/admin/banners', data: payload);
  }

  Future<void> updateBanner(int id, Map<String, dynamic> payload) async {
    await _apiClient.putMap('/admin/banners/$id', data: payload);
  }

  Future<void> deleteBanner(int id) => _apiClient.delete('/admin/banners/$id');

  Future<List<Map<String, dynamic>>> _list(String path) async {
    final data = await _apiClient.getMap(path, query: {'per_page': 100});
    return (data['data'] as List? ?? [])
        .whereType<Map>()
        .map((item) => Map<String, dynamic>.from(item))
        .toList();
  }

  Map<String, dynamic> _map(dynamic value) {
    if (value is Map<String, dynamic>) return value;
    if (value is Map) return Map<String, dynamic>.from(value);
    return const {};
  }
}
