import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../core/config/app_theme.dart';
import '../../data/models/listing_model.dart';
import '../../routes/app_routes.dart';
import '../../shared/widgets/empty_state.dart';
import '../../shared/widgets/listing_card.dart';
import 'my_listings_controller.dart';

class MyListingsPage extends GetView<MyListingsController> {
  const MyListingsPage({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('إعلاناتي'),
        actions: [
          IconButton(
            onPressed: () => Get.toNamed(AppRoutes.ownerDashboard),
            icon: const Icon(Icons.admin_panel_settings_outlined),
            tooltip: 'لوحة الإدارة',
          ),
          IconButton(
            onPressed: () => Get.toNamed(AppRoutes.addListing),
            icon: const Icon(Icons.add),
            tooltip: 'إضافة إعلان',
          ),
        ],
      ),
      body: Obx(() {
        if (!controller.isAuthenticated) {
          return Center(
            child: Padding(
              padding: const EdgeInsets.all(24),
              child: FilledButton.icon(
                onPressed: () => Get.offNamed(AppRoutes.login),
                icon: const Icon(Icons.login),
                label: const Text('تسجيل الدخول'),
              ),
            ),
          );
        }

        if (controller.isLoading.value && controller.listings.isEmpty) {
          return const Center(child: CircularProgressIndicator());
        }

        if (!controller.hasLoaded.value && controller.error.value == null) {
          Future.microtask(controller.loadAll);
          return const Center(child: CircularProgressIndicator());
        }

        if (controller.error.value != null && controller.listings.isEmpty) {
          return EmptyState(
            message: controller.error.value!,
            onRetry: controller.loadAll,
          );
        }

        if (controller.listings.isEmpty) {
          return EmptyState(
            message: 'لا توجد إعلانات حتى الآن.',
            onRetry: controller.loadAll,
          );
        }

        return RefreshIndicator(
          onRefresh: controller.loadAll,
          child: ListView.builder(
            padding: const EdgeInsets.all(16),
            itemCount: controller.listings.length,
            itemBuilder: (context, index) {
              final listing = controller.listings[index];
              return _ManagedListingCard(listing: listing);
            },
          ),
        );
      }),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () => Get.toNamed(AppRoutes.addListing),
        icon: const Icon(Icons.add),
        label: const Text('إضافة إعلان'),
      ),
    );
  }
}

class _ManagedListingCard extends GetView<MyListingsController> {
  const _ManagedListingCard({required this.listing});

  final ListingModel listing;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          ListingCard(listing: listing),
          DecoratedBox(
            decoration: BoxDecoration(
              color: Colors.white,
              border: Border.all(color: AppTheme.border),
              borderRadius: BorderRadius.circular(8),
            ),
            child: Padding(
              padding: const EdgeInsets.all(12),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  _StatusChip(status: listing.status ?? 'active'),
                  const SizedBox(height: 10),
                  Row(
                    children: [
                      Expanded(
                        child: OutlinedButton.icon(
                          onPressed: () => Get.toNamed(
                            AppRoutes.addListing,
                            arguments: {'listing': listing},
                          ),
                          icon: const Icon(Icons.edit_outlined),
                          label: const Text('تعديل'),
                        ),
                      ),
                      const SizedBox(width: 10),
                      Expanded(
                        child: OutlinedButton.icon(
                          onPressed: () => _confirmDelete(context),
                          icon: const Icon(Icons.delete_outline),
                          label: const Text('حذف'),
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  Future<void> _confirmDelete(BuildContext context) async {
    final result = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('حذف الإعلان'),
        content: Text(listing.titleAr),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('إلغاء'),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(context, true),
            child: const Text('حذف'),
          ),
        ],
      ),
    );

    if (result == true) {
      await controller.deleteListing(listing);
    }
  }
}

class _StatusChip extends StatelessWidget {
  const _StatusChip({required this.status});

  final String status;

  @override
  Widget build(BuildContext context) {
    final color = switch (status) {
      'pending' => AppTheme.secondary,
      'rejected' => Colors.red,
      _ => AppTheme.green,
    };

    return DecoratedBox(
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.12),
        borderRadius: BorderRadius.circular(8),
      ),
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 9, vertical: 5),
        child: Text(
          _label(status),
          style: TextStyle(color: color, fontWeight: FontWeight.w700),
        ),
      ),
    );
  }

  String _label(String status) {
    return switch (status) {
      'pending' => 'قيد المراجعة',
      'rejected' => 'مرفوض',
      _ => 'نشط',
    };
  }
}
