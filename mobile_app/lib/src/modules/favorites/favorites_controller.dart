import 'package:get/get.dart';

import '../../data/models/listing_model.dart';
import '../../data/repositories/favorites_repository.dart';
import '../../routes/app_routes.dart';
import '../auth/auth_controller.dart';

class FavoritesController extends GetxController {
  FavoritesController(this._repository, this._authController);

  final FavoritesRepository _repository;
  final UserAuthController _authController;

  final favorites = <String, ListingModel>{}.obs;
  final isLoading = false.obs;
  final error = RxnString();

  bool isFavorite(ListingModel listing) => favorites.containsKey(listing.slug);

  @override
  void onInit() {
    super.onInit();
    ever(_authController.user, (_) {
      if (_authController.isAuthenticated) {
        loadFavorites();
      } else {
        favorites.clear();
      }
    });
  }

  Future<void> loadFavorites() async {
    if (!_authController.isAuthenticated) {
      favorites.clear();
      return;
    }

    isLoading.value = true;
    error.value = null;

    try {
      final listings = await _repository.fetchFavorites();
      favorites.assignAll({
        for (final listing in listings) listing.slug: listing,
      });
    } catch (_) {
      error.value = 'تعذر تحميل المفضلة.';
    } finally {
      isLoading.value = false;
    }
  }

  Future<void> toggle(ListingModel listing) async {
    if (!_authController.isAuthenticated) {
      Get.snackbar('تسجيل الدخول مطلوب', 'سجل الدخول أولاً لحفظ المفضلة.');
      Get.toNamed(AppRoutes.login);
      return;
    }

    final wasFavorite = isFavorite(listing);

    if (wasFavorite) {
      favorites.remove(listing.slug);
    } else {
      favorites[listing.slug] = listing;
    }

    try {
      if (wasFavorite) {
        await _repository.removeFavorite(listing.id);
      } else {
        await _repository.addFavorite(listing.id);
      }
    } catch (_) {
      if (wasFavorite) {
        favorites[listing.slug] = listing;
      } else {
        favorites.remove(listing.slug);
      }
      Get.snackbar('تنبيه', 'تعذر تحديث المفضلة.');
    }
  }
}
