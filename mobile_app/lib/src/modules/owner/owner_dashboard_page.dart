import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../core/config/app_theme.dart';
import '../../core/services/launch_service.dart';
import '../../data/models/booking_request_model.dart';
import '../../data/repositories/booking_repository.dart';
import '../../routes/app_routes.dart';
import '../../shared/widgets/empty_state.dart';
import '../auth/auth_controller.dart';
import '../bookings/booking_controller.dart';

class OwnerDashboardPage extends StatefulWidget {
  const OwnerDashboardPage({super.key});

  @override
  State<OwnerDashboardPage> createState() => _OwnerDashboardPageState();
}

class _OwnerDashboardPageState extends State<OwnerDashboardPage> {
  late final BookingController controller;
  late final UserAuthController auth;

  @override
  void initState() {
    super.initState();
    controller = Get.find<BookingController>();
    auth = Get.find<UserAuthController>();
    Future.microtask(controller.loadOwnerDashboard);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('لوحة إدارة حسابي')),
      body: Obx(() {
        if (!auth.isAuthenticated) {
          return EmptyState(
            message: 'سجل الدخول لإدارة إعلاناتك وحجوزاتك.',
            actionLabel: 'تسجيل الدخول',
            onRetry: () => Get.offNamed(AppRoutes.login),
          );
        }

        if (controller.isOwnerLoading.value &&
            controller.ownerDashboard.value == null) {
          return const Center(child: CircularProgressIndicator());
        }

        if (controller.ownerError.value != null &&
            controller.ownerDashboard.value == null) {
          return EmptyState(
            message: controller.ownerError.value!,
            onRetry: controller.loadOwnerDashboard,
          );
        }

        final dashboard = controller.ownerDashboard.value;

        return RefreshIndicator(
          onRefresh: controller.loadOwnerDashboard,
          child: ListView(
            padding: const EdgeInsets.all(16),
            children: [
              if (dashboard != null) _StatsGrid(dashboard: dashboard),
              const SizedBox(height: 16),
              _QuickActions(),
              const SizedBox(height: 18),
              Text(
                'طلبات الحجوزات الواردة',
                style: Theme.of(context).textTheme.titleLarge,
              ),
              const SizedBox(height: 10),
              if (controller.ownerRequests.isEmpty)
                const EmptyState(message: 'لا توجد طلبات واردة حتى الآن.')
              else
                for (final request in controller.ownerRequests)
                  _OwnerRequestCard(request: request),
            ],
          ),
        );
      }),
    );
  }
}

class _StatsGrid extends StatelessWidget {
  const _StatsGrid({required this.dashboard});

  final OwnerDashboardModel dashboard;

  @override
  Widget build(BuildContext context) {
    final width = MediaQuery.sizeOf(context).width;
    final crossAxisCount = width > 760 ? 4 : 2;

    return GridView(
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      gridDelegate: SliverGridDelegateWithFixedCrossAxisCount(
        crossAxisCount: crossAxisCount,
        crossAxisSpacing: 10,
        mainAxisSpacing: 10,
        childAspectRatio: 1.65,
      ),
      children: [
        _StatTile(
          icon: Icons.campaign_outlined,
          label: 'إعلاناتي',
          value: dashboard.listingsTotal,
        ),
        _StatTile(
          icon: Icons.check_circle_outline,
          label: 'نشطة',
          value: dashboard.listingsActive,
        ),
        _StatTile(
          icon: Icons.schedule_outlined,
          label: 'طلبات جديدة',
          value: dashboard.bookingsNew,
        ),
        _StatTile(
          icon: Icons.event_available_outlined,
          label: 'حجوزات مؤكدة',
          value: dashboard.bookingsConfirmed,
        ),
      ],
    );
  }
}

class _StatTile extends StatelessWidget {
  const _StatTile({
    required this.icon,
    required this.label,
    required this.value,
  });

  final IconData icon;
  final String label;
  final int value;

  @override
  Widget build(BuildContext context) {
    return DecoratedBox(
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
            Icon(icon, color: AppTheme.primaryDark),
            const Spacer(),
            Text('$value', style: Theme.of(context).textTheme.titleLarge),
            Text(label, style: Theme.of(context).textTheme.bodySmall),
          ],
        ),
      ),
    );
  }
}

class _QuickActions extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return Wrap(
      spacing: 10,
      runSpacing: 10,
      children: [
        FilledButton.icon(
          onPressed: () => Get.toNamed(AppRoutes.addListing),
          icon: const Icon(Icons.add),
          label: const Text('إضافة إعلان'),
        ),
        OutlinedButton.icon(
          onPressed: () => Get.toNamed(AppRoutes.myListings),
          icon: const Icon(Icons.edit_calendar_outlined),
          label: const Text('إدارة إعلاناتي والأيام'),
        ),
        OutlinedButton.icon(
          onPressed: () => Get.toNamed(AppRoutes.accountType),
          icon: const Icon(Icons.badge_outlined),
          label: const Text('نوع الحساب'),
        ),
      ],
    );
  }
}

class _OwnerRequestCard extends StatelessWidget {
  const _OwnerRequestCard({required this.request});

  final BookingRequestModel request;

  @override
  Widget build(BuildContext context) {
    final launcher = Get.find<LaunchService>();
    final listing = request.listing;
    final customer = request.customer;
    final phone = request.contactPhone ?? customer?.phone ?? customer?.whatsapp;

    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: DecoratedBox(
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
              Row(
                children: [
                  Expanded(
                    child: Text(
                      listing?.titleAr ?? 'إعلان غير متاح',
                      style: Theme.of(context).textTheme.titleMedium,
                    ),
                  ),
                  _StatusBadge(
                    status: request.status,
                    label: request.statusLabel,
                  ),
                ],
              ),
              const SizedBox(height: 8),
              Text(request.dateRangeText),
              const SizedBox(height: 4),
              Text(
                [
                  if ((request.contactName ?? customer?.name ?? '').isNotEmpty)
                    request.contactName ?? customer?.name,
                  if ((phone ?? '').isNotEmpty) phone,
                ].whereType<String>().join(' - '),
                style: Theme.of(context).textTheme.bodySmall,
              ),
              if ((request.notes ?? '').isNotEmpty) ...[
                const SizedBox(height: 8),
                Text(request.notes!),
              ],
              const SizedBox(height: 12),
              Wrap(
                spacing: 8,
                runSpacing: 8,
                children: [
                  FilledButton.icon(
                    onPressed: () => _update(context, 'confirmed'),
                    icon: const Icon(Icons.check),
                    label: const Text('قبول وحجز الأيام'),
                  ),
                  OutlinedButton.icon(
                    onPressed: () => _update(context, 'in_review'),
                    icon: const Icon(Icons.hourglass_top_outlined),
                    label: const Text('قيد المراجعة'),
                  ),
                  OutlinedButton.icon(
                    onPressed: () => _update(context, 'rejected'),
                    icon: const Icon(Icons.close),
                    label: const Text('رفض'),
                  ),
                  if ((phone ?? '').isNotEmpty)
                    OutlinedButton.icon(
                      onPressed: () => launcher.callPhone(phone),
                      icon: const Icon(Icons.phone_outlined),
                      label: const Text('اتصال'),
                    ),
                  if ((phone ?? '').isNotEmpty)
                    OutlinedButton.icon(
                      onPressed: () => launcher.openWhatsapp(phone),
                      icon: const Icon(Icons.chat_outlined),
                      label: const Text('واتساب'),
                    ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }

  Future<void> _update(BuildContext context, String status) async {
    final controller = Get.find<BookingController>();
    await controller.updateOwnerRequestStatus(request, status);
  }
}

class _StatusBadge extends StatelessWidget {
  const _StatusBadge({required this.status, required this.label});

  final String status;
  final String label;

  @override
  Widget build(BuildContext context) {
    final color = switch (status) {
      'confirmed' || 'accepted' => AppTheme.green,
      'rejected' || 'cancelled' => Colors.red,
      'in_review' || 'pending' => Colors.orange,
      _ => AppTheme.primaryDark,
    };

    return DecoratedBox(
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.12),
        borderRadius: BorderRadius.circular(8),
      ),
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 9, vertical: 5),
        child: Text(
          label,
          style: TextStyle(color: color, fontWeight: FontWeight.w700),
        ),
      ),
    );
  }
}
