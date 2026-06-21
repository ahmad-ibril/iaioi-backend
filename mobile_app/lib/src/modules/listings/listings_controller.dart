import 'package:get/get.dart';

import '../../data/models/category_model.dart';
import '../../data/models/listing_model.dart';
import '../../data/repositories/catalog_repository.dart';

class ListingsController extends GetxController {
  ListingsController(this._repository, this.category, this.initialQuery);

  final CatalogRepository _repository;
  final CategoryModel? category;
  final Map<String, dynamic> initialQuery;

  final listings = <ListingModel>[].obs;
  final isLoading = false.obs;
  final error = RxnString();
  final query = <String, dynamic>{}.obs;

  Map<String, dynamic> get _lockedCategoryQuery {
    return Map.fromEntries(
      initialQuery.entries.where(
        (entry) => {
          'category',
          'category_slugs',
          'category_ids',
          'group_key',
        }.contains(entry.key),
      ),
    );
  }

  @override
  void onInit() {
    super.onInit();
    query.assignAll(initialQuery);
    loadListings();
  }

  Future<void> loadListings() async {
    isLoading.value = true;
    error.value = null;
    try {
      final result = await _repository.fetchListings(
        categorySlug: category?.slug,
        query: query,
      );
      listings.assignAll(result);
    } catch (_) {
      error.value = 'تعذر تحميل الإعلانات. تأكد من تشغيل السيرفر.';
    } finally {
      isLoading.value = false;
    }
  }

  void applyFilters(Map<String, dynamic> nextQuery) {
    query.assignAll({..._lockedCategoryQuery, ...nextQuery});
    loadListings();
  }
}
