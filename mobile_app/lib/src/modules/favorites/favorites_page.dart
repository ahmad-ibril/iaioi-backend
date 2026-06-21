import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../routes/app_routes.dart';
import '../../shared/widgets/empty_state.dart';
import '../../shared/widgets/listing_card.dart';
import '../auth/auth_controller.dart';
import 'favorites_controller.dart';

class FavoritesPage extends GetView<FavoritesController> {
  const FavoritesPage({super.key});

  @override
  Widget build(BuildContext context) {
    final auth = Get.find<UserAuthController>();

    return Scaffold(
      appBar: AppBar(title: const Text('المفضلة')),
      body: Obx(() {
        if (!auth.isAuthenticated) {
          return Center(
            child: Padding(
              padding: const EdgeInsets.all(24),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  const Icon(Icons.favorite_border, size: 52),
                  const SizedBox(height: 12),
                  const Text('سجل الدخول لحفظ المفضلة ومزامنتها.'),
                  const SizedBox(height: 16),
                  FilledButton.icon(
                    onPressed: () => Get.toNamed(AppRoutes.login),
                    icon: const Icon(Icons.login),
                    label: const Text('تسجيل الدخول'),
                  ),
                ],
              ),
            ),
          );
        }

        if (controller.isLoading.value && controller.favorites.isEmpty) {
          return const Center(child: CircularProgressIndicator());
        }

        final listings = controller.favorites.values.toList();

        if (listings.isEmpty) {
          return EmptyState(
            message:
                controller.error.value ?? 'لم تضف أي خدمة إلى المفضلة بعد.',
            onRetry: controller.loadFavorites,
          );
        }

        return RefreshIndicator(
          onRefresh: controller.loadFavorites,
          child: ListView.builder(
            padding: const EdgeInsets.all(16),
            itemCount: listings.length,
            itemBuilder: (context, index) =>
                ListingCard(listing: listings[index]),
          ),
        );
      }),
    );
  }
}
