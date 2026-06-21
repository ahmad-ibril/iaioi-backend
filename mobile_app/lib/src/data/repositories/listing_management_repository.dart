import 'package:dio/dio.dart';
import 'package:file_picker/file_picker.dart';

import '../../core/network/api_client.dart';
import '../models/category_model.dart';
import '../models/listing_model.dart';

class ListingManagementRepository {
  ListingManagementRepository(this._apiClient);

  final ApiClient _apiClient;

  Future<List<CategoryModel>> allowedCategories() async {
    final data = await _apiClient.getMap('/my/listings/allowed-categories');
    return (data['data'] as List? ?? [])
        .whereType<Map<String, dynamic>>()
        .map(CategoryModel.fromJson)
        .toList();
  }

  Future<List<ListingModel>> myListings() async {
    final data = await _apiClient.getMap(
      '/my/listings',
      query: {'per_page': 50},
    );
    return (data['data'] as List? ?? [])
        .whereType<Map<String, dynamic>>()
        .map(ListingModel.fromJson)
        .toList();
  }

  Future<ListingModel> createListing(
    Map<String, dynamic> payload, {
    List<PlatformFile> mediaFiles = const [],
  }) async {
    final data = mediaFiles.isEmpty
        ? await _apiClient.postMap('/listings', data: payload)
        : await _apiClient.postForm(
            '/listings',
            _listingFormData(payload, mediaFiles),
          );

    return _listingFromResponse(data);
  }

  Future<ListingModel> updateListing(
    ListingModel listing,
    Map<String, dynamic> payload, {
    List<PlatformFile> mediaFiles = const [],
  }) async {
    final data = await _apiClient.patchMap(
      '/listings/${listing.id}',
      data: payload,
    );
    var updated = _listingFromResponse(data);

    if (mediaFiles.isNotEmpty) {
      updated = await uploadListingMedia(updated, mediaFiles);
    }

    return updated;
  }

  Future<void> deleteListing(ListingModel listing) async {
    await _apiClient.delete('/listings/${listing.id}');
  }

  Future<ListingModel> uploadListingMedia(
    ListingModel listing,
    List<PlatformFile> mediaFiles,
  ) async {
    final data = await _apiClient.postForm(
      '/listings/${listing.id}/media',
      _listingFormData(const {}, mediaFiles),
    );

    return _listingFromResponse(data);
  }

  Future<ListingModel> setListingMediaCover(
    ListingModel listing,
    ListingMediaModel media,
  ) async {
    final data = await _apiClient.patchMap(
      '/listings/${listing.id}/media/${media.id}/cover',
    );

    return _listingFromResponse(data);
  }

  Future<ListingModel> deleteListingMedia(
    ListingModel listing,
    ListingMediaModel media,
  ) async {
    final data = await _apiClient.deleteMap(
      '/listings/${listing.id}/media/${media.id}',
    );

    return _listingFromResponse(data);
  }

  ListingModel _listingFromResponse(Map<String, dynamic> data) {
    return ListingModel.fromJson(
      data['data'] is Map<String, dynamic> ? data['data'] : data,
    );
  }

  FormData _listingFormData(
    Map<String, dynamic> payload,
    List<PlatformFile> mediaFiles,
  ) {
    final formData = FormData();

    for (final entry in payload.entries) {
      final value = entry.value;
      if (value == null) continue;

      if (entry.key == 'attributes' && value is Map) {
        for (final attribute in value.entries) {
          if (attribute.value == null) continue;
          formData.fields.add(
            MapEntry('attributes[${attribute.key}]', '${attribute.value}'),
          );
        }
        continue;
      }

      if ((entry.key == 'calendar_dates' ||
              entry.key == 'availability_slots') &&
          value is List) {
        for (var index = 0; index < value.length; index++) {
          final item = value[index];
          if (item is! Map) continue;

          for (final dateEntry in item.entries) {
            if (dateEntry.value == null) continue;
            formData.fields.add(
              MapEntry(
                '${entry.key}[$index][${dateEntry.key}]',
                '${dateEntry.value}',
              ),
            );
          }
        }
        continue;
      }

      formData.fields.add(MapEntry(entry.key, '$value'));
    }

    for (final file in mediaFiles) {
      final bytes = file.bytes;
      if (bytes == null || bytes.isEmpty) continue;

      formData.files.add(
        MapEntry(
          'uploaded_media[]',
          MultipartFile.fromBytes(bytes, filename: file.name),
        ),
      );
    }

    return formData;
  }
}
