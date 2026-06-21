import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../core/config/app_theme.dart';
import '../../data/repositories/auth_repository.dart';
import '../../routes/app_routes.dart';
import 'auth_controller.dart';

class AccountTypePage extends StatefulWidget {
  const AccountTypePage({super.key});

  @override
  State<AccountTypePage> createState() => _AccountTypePageState();
}

class _AccountTypePageState extends State<AccountTypePage> {
  String? selectedValue;

  @override
  void initState() {
    super.initState();
    final auth = Get.find<UserAuthController>();
    selectedValue = auth.user.value?.accountType;
    auth.loadAccountTypes();
  }

  @override
  Widget build(BuildContext context) {
    final auth = Get.find<UserAuthController>();

    return Scaffold(
      appBar: AppBar(title: const Text('اختيار نوع الحساب')),
      body: Obx(() {
        final user = auth.user.value;
        final options = auth.accountTypes;

        if (user == null) {
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

        if (options.isEmpty) {
          return const Center(child: CircularProgressIndicator());
        }

        return Center(
          child: ConstrainedBox(
            constraints: const BoxConstraints(maxWidth: 780),
            child: ListView(
              padding: const EdgeInsets.all(16),
              children: [
                Text(
                  'اختر صفتك داخل التطبيق',
                  style: Theme.of(context).textTheme.headlineSmall,
                  textAlign: TextAlign.center,
                ),
                const SizedBox(height: 8),
                Text(
                  'سيتم تخصيص إضافة الإعلانات والطلبات حسب نوع الحساب.',
                  style: Theme.of(
                    context,
                  ).textTheme.bodyMedium?.copyWith(color: AppTheme.textMuted),
                  textAlign: TextAlign.center,
                ),
                const SizedBox(height: 18),
                LayoutBuilder(
                  builder: (context, constraints) {
                    final width = constraints.maxWidth;
                    final crossAxisCount = width >= 720
                        ? 3
                        : width >= 520
                        ? 2
                        : 1;

                    return GridView.builder(
                      shrinkWrap: true,
                      physics: const NeverScrollableScrollPhysics(),
                      itemCount: options.length,
                      gridDelegate: SliverGridDelegateWithFixedCrossAxisCount(
                        crossAxisCount: crossAxisCount,
                        crossAxisSpacing: 12,
                        mainAxisSpacing: 12,
                        childAspectRatio: crossAxisCount == 1 ? 4.5 : 2.4,
                      ),
                      itemBuilder: (context, index) {
                        final option = options[index];
                        return _AccountTypeCard(
                          option: option,
                          selected: selectedValue == option.value,
                          onTap: () {
                            setState(() => selectedValue = option.value);
                          },
                        );
                      },
                    );
                  },
                ),
                const SizedBox(height: 18),
                Obx(
                  () => FilledButton.icon(
                    onPressed: auth.isLoading.value || selectedValue == null
                        ? null
                        : _save,
                    icon: auth.isLoading.value
                        ? const SizedBox(
                            width: 18,
                            height: 18,
                            child: CircularProgressIndicator(strokeWidth: 2),
                          )
                        : const Icon(Icons.check_circle_outline),
                    label: const Text('حفظ نوع الحساب'),
                  ),
                ),
                Obx(
                  () => auth.error.value == null
                      ? const SizedBox.shrink()
                      : Padding(
                          padding: const EdgeInsets.only(top: 12),
                          child: Text(
                            auth.error.value!,
                            textAlign: TextAlign.center,
                            style: const TextStyle(color: Colors.red),
                          ),
                        ),
                ),
              ],
            ),
          ),
        );
      }),
    );
  }

  Future<void> _save() async {
    final value = selectedValue;
    if (value == null) return;

    final ok = await Get.find<UserAuthController>().updateAccountType(value);
    if (!ok) return;

    Get.offAllNamed(AppRoutes.home);
  }
}

class _AccountTypeCard extends StatelessWidget {
  const _AccountTypeCard({
    required this.option,
    required this.selected,
    required this.onTap,
  });

  final AccountTypeOption option;
  final bool selected;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final borderColor = selected ? AppTheme.primary : AppTheme.border;
    final fillColor = selected ? AppTheme.surfaceWarm : Colors.white;

    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(8),
      child: DecoratedBox(
        decoration: BoxDecoration(
          color: fillColor,
          border: Border.all(color: borderColor, width: selected ? 1.5 : 1),
          borderRadius: BorderRadius.circular(8),
        ),
        child: Padding(
          padding: const EdgeInsets.all(12),
          child: Row(
            children: [
              DecoratedBox(
                decoration: BoxDecoration(
                  color: selected ? AppTheme.primary : AppTheme.surfaceWarm,
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Padding(
                  padding: const EdgeInsets.all(10),
                  child: Icon(
                    _iconFor(option.value),
                    color: selected ? Colors.white : AppTheme.primaryDark,
                  ),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Text(
                  option.labelAr,
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                  style: Theme.of(context).textTheme.titleSmall,
                ),
              ),
              if (selected)
                const Icon(Icons.check_circle, color: AppTheme.primary),
            ],
          ),
        ),
      ),
    );
  }

  IconData _iconFor(String value) {
    return switch (value) {
      'regular_user' => Icons.person_outline,
      'chalet_owner' => Icons.villa_outlined,
      'sports_field_owner' => Icons.sports_soccer_outlined,
      'wedding_hall_owner' => Icons.celebration_outlined,
      'hotel_owner' => Icons.apartment_outlined,
      'tourism_office_owner' => Icons.tour_outlined,
      'transport_company_owner' => Icons.directions_bus_outlined,
      'commercial_property_owner' => Icons.storefront_outlined,
      'service_provider' => Icons.work_outline,
      'technician' => Icons.handyman_outlined,
      'nursery_owner' => Icons.local_florist_outlined,
      'turkish_bath_owner' => Icons.spa_outlined,
      'amusement_city_owner' => Icons.attractions_outlined,
      'travel_agency_owner' => Icons.flight_takeoff_outlined,
      'airline_company_owner' => Icons.flight_outlined,
      'parcel_service_owner' => Icons.local_shipping_outlined,
      _ => Icons.badge_outlined,
    };
  }
}
