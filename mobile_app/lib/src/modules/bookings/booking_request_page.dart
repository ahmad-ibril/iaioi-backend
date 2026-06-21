import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../data/models/listing_model.dart';
import '../../routes/app_routes.dart';
import '../auth/auth_controller.dart';
import 'booking_controller.dart';

class BookingRequestPage extends StatefulWidget {
  const BookingRequestPage({super.key});

  @override
  State<BookingRequestPage> createState() => _BookingRequestPageState();
}

class _BookingRequestPageState extends State<BookingRequestPage> {
  final _formKey = GlobalKey<FormState>();
  final _contactNameController = TextEditingController();
  final _contactPhoneController = TextEditingController();
  final _quantityController = TextEditingController(text: '1');
  final _notesController = TextEditingController();

  DateTime? _dateFrom;
  DateTime? _dateTo;

  ListingModel? get _listing {
    final args = (Get.arguments as Map?) ?? {};
    return args['listing'] as ListingModel?;
  }

  @override
  void initState() {
    super.initState();
    final user = Get.find<UserAuthController>().user.value;
    _contactNameController.text = user?.name ?? '';
    _contactPhoneController.text = user?.phone ?? user?.whatsapp ?? '';
  }

  @override
  void dispose() {
    _contactNameController.dispose();
    _contactPhoneController.dispose();
    _quantityController.dispose();
    _notesController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final auth = Get.find<UserAuthController>();
    final controller = Get.find<BookingController>();
    final listing = _listing;

    if (listing == null) {
      return const Scaffold(body: Center(child: Text('الخدمة غير موجودة.')));
    }

    if (!auth.isAuthenticated) {
      return Scaffold(
        appBar: AppBar(title: const Text('إرسال طلب')),
        body: Center(
          child: Padding(
            padding: const EdgeInsets.all(24),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                const Icon(Icons.lock_outline, size: 54),
                const SizedBox(height: 12),
                const Text('سجل الدخول حتى تستطيع إرسال طلب.'),
                const SizedBox(height: 16),
                FilledButton.icon(
                  onPressed: () => Get.toNamed(AppRoutes.login),
                  icon: const Icon(Icons.login),
                  label: const Text('تسجيل الدخول'),
                ),
              ],
            ),
          ),
        ),
      );
    }

    final title = listing.category?.supportsBooking == false
        ? 'طلب تواصل'
        : 'طلب حجز';

    return Scaffold(
      appBar: AppBar(title: Text(title)),
      body: Form(
        key: _formKey,
        child: ListView(
          padding: const EdgeInsets.all(16),
          children: [
            Card(
              child: ListTile(
                title: Text(listing.titleAr),
                subtitle: Text(
                  listing.locationText.isEmpty
                      ? listing.priceText
                      : '${listing.locationText}\n${listing.priceText}',
                ),
                isThreeLine: listing.locationText.isNotEmpty,
              ),
            ),
            const SizedBox(height: 12),
            Row(
              children: [
                Expanded(
                  child: _DateField(
                    label: 'من تاريخ',
                    value: _dateFrom,
                    onPick: () => _pickDate(isFrom: true),
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: _DateField(
                    label: 'إلى تاريخ',
                    value: _dateTo,
                    onPick: () => _pickDate(isFrom: false),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 12),
            TextFormField(
              controller: _quantityController,
              keyboardType: TextInputType.number,
              decoration: const InputDecoration(
                labelText: 'الكمية',
                prefixIcon: Icon(Icons.numbers_outlined),
              ),
              validator: (value) {
                final quantity = int.tryParse(value ?? '');
                if (quantity == null || quantity < 1) return 'أدخل كمية صحيحة';
                return null;
              },
            ),
            const SizedBox(height: 12),
            TextFormField(
              controller: _contactNameController,
              decoration: const InputDecoration(
                labelText: 'اسم التواصل',
                prefixIcon: Icon(Icons.person_outline),
              ),
            ),
            const SizedBox(height: 12),
            TextFormField(
              controller: _contactPhoneController,
              keyboardType: TextInputType.phone,
              decoration: const InputDecoration(
                labelText: 'رقم التواصل',
                prefixIcon: Icon(Icons.phone_outlined),
              ),
            ),
            const SizedBox(height: 12),
            TextFormField(
              controller: _notesController,
              minLines: 4,
              maxLines: 6,
              decoration: const InputDecoration(
                labelText: 'ملاحظات',
                alignLabelWithHint: true,
              ),
            ),
            const SizedBox(height: 20),
            Obx(
              () => FilledButton.icon(
                onPressed: controller.isSubmitting.value
                    ? null
                    : () => _submit(controller, listing),
                icon: controller.isSubmitting.value
                    ? const SizedBox(
                        width: 18,
                        height: 18,
                        child: CircularProgressIndicator(strokeWidth: 2),
                      )
                    : const Icon(Icons.send_outlined),
                label: Text(
                  controller.isSubmitting.value
                      ? 'جاري الإرسال'
                      : 'إرسال الطلب',
                ),
              ),
            ),
            Obx(() {
              final error = controller.error.value;
              if (error == null) return const SizedBox.shrink();
              return Padding(
                padding: const EdgeInsets.only(top: 12),
                child: Text(
                  error,
                  style: TextStyle(color: Theme.of(context).colorScheme.error),
                ),
              );
            }),
          ],
        ),
      ),
    );
  }

  Future<void> _pickDate({required bool isFrom}) async {
    final now = DateTime.now();
    final picked = await showDatePicker(
      context: context,
      locale: const Locale('ar'),
      initialDate: isFrom ? (_dateFrom ?? now) : (_dateTo ?? _dateFrom ?? now),
      firstDate: now,
      lastDate: now.add(const Duration(days: 730)),
    );

    if (picked == null) return;

    setState(() {
      if (isFrom) {
        _dateFrom = picked;
        if (_dateTo != null && _dateTo!.isBefore(picked)) {
          _dateTo = picked;
        }
      } else {
        _dateTo = picked;
      }
    });
  }

  Future<void> _submit(
    BookingController controller,
    ListingModel listing,
  ) async {
    if (!_formKey.currentState!.validate()) return;

    final success = await controller.create(
      listingId: listing.id,
      dateFrom: _dateFrom,
      dateTo: _dateTo,
      quantity: int.tryParse(_quantityController.text) ?? 1,
      contactName: _contactNameController.text,
      contactPhone: _contactPhoneController.text,
      notes: _notesController.text,
    );

    if (success) {
      Get.offNamed(AppRoutes.myRequests);
    }
  }
}

class _DateField extends StatelessWidget {
  const _DateField({
    required this.label,
    required this.value,
    required this.onPick,
  });

  final String label;
  final DateTime? value;
  final VoidCallback onPick;

  @override
  Widget build(BuildContext context) {
    return TextFormField(
      readOnly: true,
      onTap: onPick,
      decoration: InputDecoration(
        labelText: label,
        prefixIcon: const Icon(Icons.event_outlined),
      ),
      controller: TextEditingController(
        text: value == null ? '' : _dateText(value!),
      ),
    );
  }
}

String _dateText(DateTime date) {
  final month = date.month.toString().padLeft(2, '0');
  final day = date.day.toString().padLeft(2, '0');
  return '${date.year}-$month-$day';
}
