import 'package:get/get.dart';

import '../../core/services/location_service.dart';
import '../../data/models/category_model.dart';
import '../../data/models/listing_model.dart';
import '../../data/repositories/catalog_repository.dart';

class HomeController extends GetxController {
  HomeController(this._repository, this._locationService);

  final CatalogRepository _repository;
  final LocationService _locationService;

  final categories = <CategoryModel>[].obs;
  final latestListings = <ListingModel>[].obs;
  final isLoading = false.obs;
  final error = RxnString();

  @override
  void onInit() {
    super.onInit();
    loadHome();
  }

  Future<void> loadHome() async {
    isLoading.value = true;
    error.value = null;
    try {
      final results = await Future.wait([
        _repository.fetchCategories(),
        _repository.fetchListings(query: {'per_page': 10}),
      ]);
      categories.assignAll(results[0] as List<CategoryModel>);
      latestListings.assignAll(results[1] as List<ListingModel>);
    } catch (_) {
      error.value = 'تعذر تحميل البيانات. تأكد من تشغيل السيرفر.';
    } finally {
      isLoading.value = false;
    }
  }

  Future<void> sortByNearest(CategoryModel category) async {
    final position = await _locationService.requestCurrentLocation();
    final query = <String, dynamic>{'sort': 'distance'};
    if (position != null) {
      query['latitude'] = position.latitude;
      query['longitude'] = position.longitude;
    }
    Get.toNamed('/listings', arguments: {'category': category, 'query': query});
  }
}
