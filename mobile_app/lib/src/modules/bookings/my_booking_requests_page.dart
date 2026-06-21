import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../data/models/booking_request_model.dart';
import '../../routes/app_routes.dart';
import '../../shared/widgets/empty_state.dart';
import '../auth/auth_controller.dart';
import 'booking_controller.dart';

class MyBookingRequestsPage extends StatefulWidget {
  const MyBookingRequestsPage({super.key});

  @override
  State<MyBookingRequestsPage> createState() => _MyBookingRequestsPageState();
}

class _MyBookingRequestsPageState extends State<MyBookingRequestsPage> {
  late final BookingController _controller;
  late final UserAuthController _authController;

  @override
  void initState() {
    super.initState();
    _controller = Get.find<BookingController>();
    _authController = Get.find<UserAuthController>();
    Future.microtask(_controller.loadRequests);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('طلباتي')),
      body: Obx(() {
        if (!_authController.isAuthenticated) {
          return EmptyState(
            message: 'سجل الدخول لعرض طلباتك.',
            actionLabel: 'تسجيل الدخول',
            onRetry: () => Get.toNamed(AppRoutes.login),
          );
        }

        if (_controller.isLoading.value) {
          return const Center(child: CircularProgressIndicator());
        }

        if (_controller.error.value != null) {
          return EmptyState(
            message: _controller.error.value!,
            onRetry: _controller.loadRequests,
          );
        }

        if (_controller.requests.isEmpty) {
          return EmptyState(
            message: 'لا توجد طلبات حتى الآن.',
            actionLabel: 'تصفح الخدمات',
            onRetry: () => Get.offAllNamed(AppRoutes.home),
          );
        }

        return RefreshIndicator(
          onRefresh: _controller.loadRequests,
          child: ListView.separated(
            padding: const EdgeInsets.all(16),
            itemCount: _controller.requests.length,
            separatorBuilder: (context, index) => const SizedBox(height: 10),
            itemBuilder: (context, index) {
              return _RequestCard(
                request: _controller.requests[index],
                onCancel: () => _controller.cancel(_controller.requests[index]),
              );
            },
          ),
        );
      }),
    );
  }
}

class _RequestCard extends StatelessWidget {
  const _RequestCard({required this.request, required this.onCancel});

  final BookingRequestModel request;
  final VoidCallback onCancel;

  @override
  Widget build(BuildContext context) {
    final listing = request.listing;

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(14),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Expanded(
                  child: Text(
                    listing?.titleAr ?? 'خدمة غير متاحة',
                    style: Theme.of(context).textTheme.titleMedium,
                  ),
                ),
                Chip(label: Text(request.statusLabel)),
              ],
            ),
            const SizedBox(height: 8),
            Text(request.dateRangeText),
            if ((listing?.locationText ?? '').isNotEmpty) ...[
              const SizedBox(height: 4),
              Text(
                listing!.locationText,
                style: Theme.of(context).textTheme.bodySmall,
              ),
            ],
            if ((request.notes ?? '').isNotEmpty) ...[
              const SizedBox(height: 8),
              Text(request.notes!),
            ],
            if ((request.adminNotes ?? '').isNotEmpty) ...[
              const SizedBox(height: 8),
              Text(
                request.adminNotes!,
                style: Theme.of(context).textTheme.bodySmall,
              ),
            ],
            if (request.canCancel) ...[
              const SizedBox(height: 10),
              Align(
                alignment: AlignmentDirectional.centerEnd,
                child: TextButton.icon(
                  onPressed: onCancel,
                  icon: const Icon(Icons.close),
                  label: const Text('إلغاء الطلب'),
                ),
              ),
            ],
          ],
        ),
      ),
    );
  }
}
