import 'package:dio/dio.dart';
import 'package:get/get.dart';

import '../../data/models/category_model.dart';
import '../../data/models/listing_model.dart';
import '../../data/repositories/listing_management_repository.dart';
import '../auth/auth_controller.dart';

class MyListingsController extends GetxController {
  MyListingsController(this._repository, this._authController);

  final ListingManagementRepository _repository;
  final UserAuthController _authController;

  final listings = <ListingModel>[].obs;
  final allowedCategories = <CategoryModel>[].obs;
  final isLoading = false.obs;
  final hasLoaded = false.obs;
  final error = RxnString();

  bool get isAuthenticated => _authController.isAuthenticated;

  @override
  void onInit() {
    super.onInit();
    if (isAuthenticated) {
      loadAll();
    }
  }

  Future<void> loadAll() async {
    isLoading.value = true;
    error.value = null;

    try {
      final results = await Future.wait([
        _repository.myListings(),
        _repository.allowedCategories(),
      ]);
      listings.assignAll(results[0] as List<ListingModel>);
      allowedCategories.assignAll(results[1] as List<CategoryModel>);
      hasLoaded.value = true;
    } on DioException catch (exception) {
      error.value = _message(exception);
    } catch (_) {
      error.value = 'تعذر تحميل إعلاناتك.';
    } finally {
      isLoading.value = false;
    }
  }

  Future<bool> deleteListing(ListingModel listing) async {
    isLoading.value = true;
    error.value = null;

    try {
      await _repository.deleteListing(listing);
      listings.removeWhere((item) => item.id == listing.id);
      return true;
    } on DioException catch (exception) {
      error.value = _message(exception);
      return false;
    } catch (_) {
      error.value = 'تعذر حذف الإعلان.';
      return false;
    } finally {
      isLoading.value = false;
    }
  }

  String _message(DioException exception) {
    final data = exception.response?.data;
    if (data is Map<String, dynamic>) {
      final message = data['message'];
      if (message is String && message.isNotEmpty) return message;
    }

    return 'تعذر الاتصال بالخادم.';
  }
}
