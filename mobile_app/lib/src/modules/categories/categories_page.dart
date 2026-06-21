import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../core/utils/category_presentation.dart';
import '../../routes/app_routes.dart';
import '../../shared/widgets/category_card.dart';
import '../../shared/widgets/empty_state.dart';
import '../../shared/widgets/main_bottom_navigation.dart';
import '../home/home_controller.dart';

class CategoriesPage extends GetView<HomeController> {
  const CategoriesPage({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('الأقسام')),
      body: Obx(() {
        final categories = CategoryPresentation.groupedCategories(
          controller.categories,
        );

        if (controller.isLoading.value && categories.isEmpty) {
          return const Center(child: CircularProgressIndicator());
        }
        if (controller.error.value != null && categories.isEmpty) {
          return EmptyState(
            message: controller.error.value!,
            onRetry: controller.loadHome,
          );
        }
        if (categories.isEmpty) {
          return EmptyState(
            message: 'لا توجد أقسام.',
            onRetry: controller.loadHome,
          );
        }

        final width = MediaQuery.sizeOf(context).width;
        final crossAxisCount = width > 900 ? 4 : (width > 560 ? 3 : 2);

        return RefreshIndicator(
          onRefresh: controller.loadHome,
          child: Center(
            child: ConstrainedBox(
              constraints: const BoxConstraints(maxWidth: 900),
              child: GridView.builder(
                padding: const EdgeInsets.all(16),
                itemCount: categories.length,
                gridDelegate: SliverGridDelegateWithFixedCrossAxisCount(
                  crossAxisCount: crossAxisCount,
                  crossAxisSpacing: 12,
                  mainAxisSpacing: 12,
                  childAspectRatio: 1.06,
                ),
                itemBuilder: (context, index) {
                  final category = categories[index];
                  return CategoryCard(
                    category: category,
                    onTap: () => Get.toNamed(
                      AppRoutes.listings,
                      arguments: CategoryPresentation.routeArguments(category),
                    ),
                  );
                },
              ),
            ),
          ),
        );
      }),
      bottomNavigationBar: const MainBottomNavigation(selectedIndex: 2),
    );
  }
}
