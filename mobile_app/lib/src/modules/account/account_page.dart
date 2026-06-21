import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../routes/app_routes.dart';
import '../../shared/widgets/main_bottom_navigation.dart';
import '../auth/auth_controller.dart';
import '../favorites/favorites_controller.dart';

class AccountPage extends GetView<UserAuthController> {
  const AccountPage({super.key});

  @override
  Widget build(BuildContext context) {
    final favorites = Get.find<FavoritesController>();

    return Scaffold(
      appBar: AppBar(title: const Text('حسابي')),
      body: Obx(() {
        final user = controller.user.value;

        if (controller.isLoading.value && user == null) {
          return const Center(child: CircularProgressIndicator());
        }

        if (user == null) {
          return Center(
            child: Padding(
              padding: const EdgeInsets.all(24),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  const Icon(Icons.person_outline, size: 54),
                  const SizedBox(height: 12),
                  const Text('سجل الدخول لإدارة حسابك ومفضلتك.'),
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

        return Center(
          child: ConstrainedBox(
            constraints: const BoxConstraints(maxWidth: 680),
            child: ListView(
              padding: const EdgeInsets.all(16),
              children: [
                Card(
                  child: Padding(
                    padding: const EdgeInsets.all(16),
                    child: Row(
                      children: [
                        CircleAvatar(
                          radius: 28,
                          child: Text(
                            user.name.isEmpty
                                ? 'U'
                                : user.name.characters.first,
                          ),
                        ),
                        const SizedBox(width: 14),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                user.name,
                                style: Theme.of(context).textTheme.titleMedium,
                              ),
                              if ((user.email ?? '').isNotEmpty)
                                Text(
                                  user.email!,
                                  style: Theme.of(context).textTheme.bodySmall,
                                ),
                              if ((user.phone ?? '').isNotEmpty)
                                Text(
                                  user.phone!,
                                  style: Theme.of(context).textTheme.bodySmall,
                                ),
                              if ((user.accountTypeLabel ?? '').isNotEmpty)
                                Text(
                                  user.accountTypeLabel!,
                                  style: Theme.of(context).textTheme.bodySmall,
                                ),
                            ],
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
                _AccountTile(
                  icon: Icons.badge_outlined,
                  title: 'نوع الحساب',
                  subtitle: user.accountTypeLabel ?? 'مستخدم عادي',
                  onTap: () => Get.toNamed(AppRoutes.accountType),
                ),
                if (user.role == 'admin')
                  _AccountTile(
                    icon: Icons.admin_panel_settings_outlined,
                    title: 'لوحة تحكم الأدمن',
                    subtitle:
                        'إدارة الإعلانات والمستخدمين والحجوزات والإعدادات',
                    onTap: () => Get.toNamed(AppRoutes.adminDashboard),
                  ),
                _AccountTile(
                  icon: Icons.admin_panel_settings_outlined,
                  title: 'إدارة حسابي',
                  subtitle: 'الحجوزات والإعلانات والأيام المتاحة',
                  onTap: () => Get.toNamed(AppRoutes.ownerDashboard),
                ),
                _AccountTile(
                  icon: Icons.add_circle_outline,
                  title: 'إضافة إعلان',
                  subtitle: 'إضافة إعلان حسب نوع حسابك',
                  onTap: () => Get.toNamed(AppRoutes.addListing),
                ),
                _AccountTile(
                  icon: Icons.campaign_outlined,
                  title: 'إعلاناتي',
                  subtitle: 'تعديل وحذف وإدارة الأيام',
                  onTap: () => Get.toNamed(AppRoutes.myListings),
                ),
                Card(
                  child: ListTile(
                    leading: const Icon(Icons.favorite_outline),
                    title: const Text('المفضلة'),
                    subtitle: Obx(
                      () => Text('${favorites.favorites.length} إعلان محفوظ'),
                    ),
                    onTap: () => Get.toNamed(AppRoutes.favorites),
                  ),
                ),
                _AccountTile(
                  icon: Icons.receipt_long_outlined,
                  title: 'طلباتي',
                  subtitle: 'متابعة طلبات الحجز والتواصل',
                  onTap: () => Get.toNamed(AppRoutes.myRequests),
                ),
                Card(
                  child: ListTile(
                    leading: const Icon(Icons.logout),
                    title: const Text('تسجيل الخروج'),
                    onTap: () async {
                      await controller.logout();
                      favorites.favorites.clear();
                      Get.offAllNamed(AppRoutes.home);
                    },
                  ),
                ),
              ],
            ),
          ),
        );
      }),
      bottomNavigationBar: const MainBottomNavigation(selectedIndex: 4),
    );
  }
}

class _AccountTile extends StatelessWidget {
  const _AccountTile({
    required this.icon,
    required this.title,
    required this.subtitle,
    required this.onTap,
  });

  final IconData icon;
  final String title;
  final String subtitle;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Card(
      child: ListTile(
        leading: Icon(icon),
        title: Text(title),
        subtitle: Text(subtitle),
        trailing: const Icon(Icons.chevron_left),
        onTap: onTap,
      ),
    );
  }
}
