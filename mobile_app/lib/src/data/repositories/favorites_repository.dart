import '../../core/network/api_client.dart';
import '../models/listing_model.dart';

class FavoritesRepository {
  FavoritesRepository(this._apiClient);

  final ApiClient _apiClient;

  Future<List<ListingModel>> fetchFavorites() async {
    final data = await _apiClient.getMap('/favorites');
    return (data['data'] as List? ?? [])
        .whereType<Map<String, dynamic>>()
        .map(ListingModel.fromJson)
        .toList();
  }

  Future<void> addFavorite(int listingId) async {
    await _apiClient.postMap('/favorites', data: {'listing_id': listingId});
  }

  Future<void> removeFavorite(int listingId) async {
    await _apiClient.delete('/favorites/$listingId');
  }
}
