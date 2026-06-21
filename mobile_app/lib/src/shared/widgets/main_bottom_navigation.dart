import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../core/config/app_theme.dart';
import '../../routes/app_routes.dart';

class MainBottomNavigation extends StatelessWidget {
  const MainBottomNavigation({super.key, required this.selectedIndex});

  final int selectedIndex;

  @override
  Widget build(BuildContext context) {
    return AppBottomNav(selectedIndex: selectedIndex);
  }
}

class AppBottomNav extends StatelessWidget {
  const AppBottomNav({super.key, required this.selectedIndex});

  final int selectedIndex;

  @override
  Widget build(BuildContext context) {
    return SafeArea(
      top: false,
      child: SizedBox(
        height: 88,
        child: Stack(
          clipBehavior: Clip.none,
          alignment: Alignment.topCenter,
          children: [
            Positioned.fill(
              top: 16,
              child: DecoratedBox(
                decoration: BoxDecoration(
                  color: Colors.white,
                  border: const Border(top: BorderSide(color: AppTheme.border)),
                  boxShadow: [
                    BoxShadow(
                      color: AppTheme.primaryDark.withValues(alpha: 0.08),
                      blurRadius: 18,
                      offset: const Offset(0, -8),
                    ),
                  ],
                ),
                child: Padding(
                  padding: const EdgeInsets.fromLTRB(8, 8, 8, 4),
                  child: Row(
                    textDirection: TextDirection.rtl,
                    mainAxisAlignment: MainAxisAlignment.spaceAround,
                    children: [
                      _BottomItem(
                        icon: Icons.home_outlined,
                        selectedIcon: Icons.home,
                        label: 'الرئيسية',
                        selected: selectedIndex == 0,
                        onTap: () => _go(AppRoutes.home),
                      ),
                      _BottomItem(
                        icon: Icons.campaign_outlined,
                        selectedIcon: Icons.campaign,
                        label: 'الإعلانات',
                        selected: selectedIndex == 1,
                        onTap: () => _go(AppRoutes.allListings),
                      ),
                      const SizedBox(width: 64),
                      _BottomItem(
                        icon: Icons.grid_view_outlined,
                        selectedIcon: Icons.grid_view,
                        label: 'الأقسام',
                        selected: selectedIndex == 2,
                        onTap: () => _go(AppRoutes.categories),
                      ),
                      _BottomItem(
                        icon: Icons.person_outline,
                        selectedIcon: Icons.person,
                        label: 'حسابي',
                        selected: selectedIndex == 4,
                        onTap: () => _go(AppRoutes.account),
                      ),
                    ],
                  ),
                ),
              ),
            ),
            Positioned(
              top: 0,
              child: Material(
                color: AppTheme.primary,
                elevation: 8,
                shadowColor: AppTheme.primaryDark.withValues(alpha: 0.28),
                shape: const CircleBorder(),
                child: InkWell(
                  customBorder: const CircleBorder(),
                  onTap: () => _go(AppRoutes.addListing),
                  child: const SizedBox(
                    width: 60,
                    height: 60,
                    child: Icon(Icons.add, color: Colors.white, size: 31),
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  void _go(String route) {
    if (Get.currentRoute == route) return;
    Get.toNamed(route);
  }
}

class _BottomItem extends StatelessWidget {
  const _BottomItem({
    required this.icon,
    required this.selectedIcon,
    required this.label,
    required this.selected,
    required this.onTap,
  });

  final IconData icon;
  final IconData selectedIcon;
  final String label;
  final bool selected;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final color = selected ? AppTheme.primaryDark : AppTheme.textMuted;

    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(8),
      child: SizedBox(
        width: 62,
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(selected ? selectedIcon : icon, color: color, size: 23),
            const SizedBox(height: 4),
            Text(
              label,
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: TextStyle(
                color: color,
                fontSize: 11,
                fontWeight: selected ? FontWeight.w800 : FontWeight.w600,
              ),
            ),
          ],
        ),
      ),
    );
  }
}
