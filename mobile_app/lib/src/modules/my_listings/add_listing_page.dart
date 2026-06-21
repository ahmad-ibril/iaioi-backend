import 'package:dio/dio.dart';
import 'package:file_picker/file_picker.dart';
import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../core/config/app_theme.dart';
import '../../core/services/location_service.dart';
import '../../core/utils/category_presentation.dart';
import '../../data/models/availability_slot_model.dart';
import '../../data/models/calendar_date_model.dart';
import '../../data/models/category_model.dart';
import '../../data/models/listing_model.dart';
import '../../data/repositories/listing_management_repository.dart';
import '../../routes/app_routes.dart';
import '../../shared/widgets/availability_calendar.dart';
import '../../shared/widgets/empty_state.dart';
import '../auth/auth_controller.dart';
import 'my_listings_controller.dart';

class AddListingPage extends StatefulWidget {
  const AddListingPage({super.key});

  @override
  State<AddListingPage> createState() => _AddListingPageState();
}

class _AddListingPageState extends State<AddListingPage> {
  final formKey = GlobalKey<FormState>();
  final titleController = TextEditingController();
  final descriptionController = TextEditingController();
  final priceController = TextEditingController();
  final areaController = TextEditingController();
  final addressController = TextEditingController();
  final phoneController = TextEditingController();
  final whatsappController = TextEditingController();
  final latitudeController = TextEditingController();
  final longitudeController = TextEditingController();

  final attributes = <String, dynamic>{};
  final calendarDates = <CalendarDateModel>[];
  final availabilitySlots = <AvailabilitySlotModel>[];
  final selectedMediaFiles = <PlatformFile>[];

  ListingModel? editingListing;
  List<CategoryModel> categories = const [];
  CategoryModel? selectedCategory;
  String priceUnit = 'day';
  String status = 'active';
  int currentStep = 0;
  bool isLoading = true;
  bool isSaving = false;
  String? error;
  String? availabilityPaintStatus;
  int _localSlotId = -1;

  bool get isEditing => editingListing != null;

  @override
  void initState() {
    super.initState();
    final args = (Get.arguments as Map?) ?? {};
    editingListing = args['listing'] as ListingModel?;
    _prefill(editingListing);
    _loadCategories();
  }

  @override
  void dispose() {
    titleController.dispose();
    descriptionController.dispose();
    priceController.dispose();
    areaController.dispose();
    addressController.dispose();
    phoneController.dispose();
    whatsappController.dispose();
    latitudeController.dispose();
    longitudeController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final auth = Get.find<UserAuthController>();

    return Scaffold(
      appBar: AppBar(title: Text(isEditing ? 'تعديل إعلان' : 'إضافة إعلان')),
      body: Obx(() {
        if (!auth.isAuthenticated) {
          return EmptyState(
            message: 'سجل الدخول لإضافة إعلان وإدارة إعلاناتك.',
            onRetry: () => Get.offNamed(AppRoutes.login),
          );
        }

        if (isLoading) return const Center(child: CircularProgressIndicator());

        return Center(
          child: ConstrainedBox(
            constraints: const BoxConstraints(maxWidth: 820),
            child: Form(
              key: formKey,
              child: Stepper(
                type: StepperType.vertical,
                currentStep: currentStep,
                onStepTapped: (step) => setState(() => currentStep = step),
                controlsBuilder: _controls,
                steps: [
                  Step(
                    title: const Text('معلومات أساسية'),
                    isActive: currentStep >= 0,
                    content: _BasicStep(
                      titleController: titleController,
                      descriptionController: descriptionController,
                      areaController: areaController,
                      addressController: addressController,
                      phoneController: phoneController,
                      whatsappController: whatsappController,
                      latitudeController: latitudeController,
                      longitudeController: longitudeController,
                      onUseCurrentLocation: _useCurrentLocation,
                      categories: categories,
                      selectedCategory: selectedCategory,
                      onCategoryChanged: _changeCategory,
                      requiredValidator: _required,
                    ),
                  ),
                  Step(
                    title: const Text('السعر والتفاصيل'),
                    isActive: currentStep >= 1,
                    content: _PriceDetailsStep(
                      priceController: priceController,
                      priceUnit: priceUnit,
                      selectedCategory: selectedCategory,
                      attributes: attributes,
                      onPriceUnitChanged: (value) {
                        setState(() => priceUnit = value ?? 'day');
                      },
                      onAttributeChanged: (key, value) {
                        setState(() {
                          if (value == null || value == '') {
                            attributes.remove(key);
                          } else {
                            attributes[key] = value;
                          }
                        });
                      },
                    ),
                  ),
                  Step(
                    title: const Text('الصور'),
                    isActive: currentStep >= 2,
                    content: _ImagesStep(
                      selectedFiles: selectedMediaFiles,
                      existingMedia: editingListing?.media ?? const [],
                      onPick: _pickMediaFiles,
                      onRemove: (file) {
                        setState(() => selectedMediaFiles.remove(file));
                      },
                    ),
                  ),
                  Step(
                    title: const Text('التوفر والحجوزات'),
                    isActive: currentStep >= 3,
                    content: _AvailabilityStep(
                      enabled: CategoryPresentation.showsAvailability(
                        selectedCategory,
                      ),
                      slots: availabilitySlots,
                      paintStatus: availabilityPaintStatus,
                      onPaintStatusChanged: (value) {
                        setState(() => availabilityPaintStatus = value);
                      },
                      onDayTap: _handleAvailabilityDayTap,
                      onAvailableAll: () => _markAllSlots('available'),
                      onUnavailableAll: () => _markAllSlots('unavailable'),
                      onClear: () {
                        setState(() {
                          availabilitySlots.clear();
                          calendarDates.clear();
                          availabilityPaintStatus = null;
                        });
                      },
                    ),
                  ),
                  Step(
                    title: const Text('مراجعة ونشر'),
                    isActive: currentStep >= 4,
                    content: _ReviewStep(
                      title: titleController.text,
                      category: selectedCategory,
                      area: areaController.text,
                      price: priceController.text,
                      priceUnit: priceUnit,
                      mediaCount:
                          (editingListing?.media.length ?? 0) +
                          selectedMediaFiles.length,
                      datesCount: availabilitySlots.length,
                      error: error,
                      isSaving: isSaving,
                    ),
                  ),
                ],
              ),
            ),
          ),
        );
      }),
    );
  }

  Widget _controls(BuildContext context, ControlsDetails details) {
    final isLast = currentStep == 4;

    return Padding(
      padding: const EdgeInsets.only(top: 16),
      child: Row(
        children: [
          Expanded(
            child: FilledButton.icon(
              onPressed: isSaving ? null : (isLast ? _save : _nextStep),
              icon: isSaving
                  ? const SizedBox(
                      width: 18,
                      height: 18,
                      child: CircularProgressIndicator(strokeWidth: 2),
                    )
                  : Icon(isLast ? Icons.publish_outlined : Icons.arrow_back),
              label: Text(isLast ? 'نشر الإعلان' : 'التالي'),
            ),
          ),
          if (currentStep > 0) ...[
            const SizedBox(width: 10),
            OutlinedButton(
              onPressed: isSaving
                  ? null
                  : () => setState(() => currentStep = currentStep - 1),
              child: const Text('رجوع'),
            ),
          ],
        ],
      ),
    );
  }

  void _nextStep() {
    if (currentStep == 0 && !formKey.currentState!.validate()) return;
    setState(() => currentStep = (currentStep + 1).clamp(0, 4));
  }

  Future<void> _loadCategories() async {
    setState(() {
      isLoading = true;
      error = null;
    });

    try {
      final result = await Get.find<ListingManagementRepository>()
          .allowedCategories();
      final existingCategory = editingListing?.category;
      final list = [
        ...result,
        if (existingCategory != null &&
            result.every((category) => category.id != existingCategory.id))
          existingCategory,
      ];

      setState(() {
        categories = list;
        selectedCategory ??= existingCategory ?? list.firstOrNull;
        if (CategoryPresentation.usesProductLayout(selectedCategory)) {
          priceUnit = 'product';
        }
      });
    } on DioException catch (exception) {
      setState(() => error = _message(exception));
    } catch (_) {
      setState(() => error = 'تعذر تحميل الأقسام المسموحة.');
    } finally {
      if (mounted) setState(() => isLoading = false);
    }
  }

  void _changeCategory(int? value) {
    setState(() {
      selectedCategory = categories.firstWhereOrNull(
        (category) => category.id == value,
      );
      attributes.clear();
      calendarDates.clear();
      availabilitySlots.clear();
      availabilityPaintStatus = null;
      priceUnit = CategoryPresentation.usesProductLayout(selectedCategory)
          ? 'product'
          : 'day';
    });
  }

  Future<void> _pickMediaFiles() async {
    final result = await FilePicker.platform.pickFiles(
      allowMultiple: true,
      withData: true,
      type: FileType.custom,
      allowedExtensions: const [
        'jpg',
        'jpeg',
        'png',
        'webp',
        'gif',
        'mp4',
        'mov',
        'webm',
        'avi',
      ],
    );

    if (result == null) return;
    setState(() {
      selectedMediaFiles.addAll(
        result.files.where((file) => file.bytes != null),
      );
    });
  }

  Future<void> _useCurrentLocation() async {
    final position = await Get.find<LocationService>().requestCurrentLocation();
    if (position == null) return;

    setState(() {
      latitudeController.text = position.latitude.toStringAsFixed(7);
      longitudeController.text = position.longitude.toStringAsFixed(7);
    });
  }

  void _handleAvailabilityDayTap(DateTime day) {
    final status = availabilityPaintStatus;
    if (status != null) {
      setState(() => _upsertDefaultSlot(day, status));
      return;
    }

    _showDaySlots(day);
  }

  void _markAllSlots(String status) {
    final today = DateUtils.dateOnly(DateTime.now());
    setState(() {
      for (var i = 0; i <= 365; i++) {
        _upsertDefaultSlot(today.add(Duration(days: i)), status);
      }
      availabilityPaintStatus = null;
    });
  }

  void _upsertDefaultSlot(DateTime day, String status) {
    final date = DateUtils.dateOnly(day);
    availabilitySlots.removeWhere((slot) {
      return DateUtils.isSameDay(slot.date, date) &&
          ((slot.startTime ?? '').isEmpty && (slot.endTime ?? '').isEmpty);
    });
    availabilitySlots.add(_defaultSlot(date, status));
    availabilitySlots.sort((a, b) {
      final byDate = a.date.compareTo(b.date);
      if (byDate != 0) return byDate;
      return (a.startTime ?? '').compareTo(b.startTime ?? '');
    });
  }

  AvailabilitySlotModel _defaultSlot(DateTime day, String status) {
    final slotName = switch (status) {
      'reserved' => 'محجوز',
      'unavailable' => 'غير متاح',
      _ => 'يوم كامل',
    };

    return AvailabilitySlotModel(
      id: _localSlotId--,
      listingId: editingListing?.id ?? 0,
      date: DateUtils.dateOnly(day),
      slotName: slotName,
      status: status,
      price: priceController.text.trim().isEmpty
          ? null
          : priceController.text.trim(),
    );
  }

  Future<void> _showDaySlots(DateTime day) async {
    final date = DateUtils.dateOnly(day);

    await showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      builder: (context) {
        final daySlots = availabilitySlots
            .where((slot) => DateUtils.isSameDay(slot.date, date))
            .toList();

        return Directionality(
          textDirection: TextDirection.rtl,
          child: SafeArea(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Expanded(
                        child: Text(
                          'فترات ${AvailabilitySlotModel.formatDate(date)}',
                          style: Theme.of(context).textTheme.titleLarge,
                        ),
                      ),
                      IconButton(
                        onPressed: () => Navigator.pop(context),
                        icon: const Icon(Icons.close),
                      ),
                    ],
                  ),
                  const SizedBox(height: 8),
                  if (daySlots.isEmpty)
                    Text(
                      'لا توجد فترات لهذا اليوم بعد.',
                      style: Theme.of(context).textTheme.bodySmall,
                    )
                  else
                    for (final slot in daySlots)
                      ListTile(
                        contentPadding: EdgeInsets.zero,
                        leading: CircleAvatar(
                          backgroundColor: slot.statusColor.withValues(
                            alpha: 0.14,
                          ),
                          child: Icon(Icons.schedule, color: slot.statusColor),
                        ),
                        title: Text(slot.slotName),
                        subtitle: Text(
                          [
                            slot.timeText,
                            if ((slot.price ?? '').isNotEmpty)
                              '${slot.price} JOD',
                            slot.statusLabel,
                          ].join(' - '),
                        ),
                        trailing: Wrap(
                          spacing: 4,
                          children: [
                            IconButton(
                              onPressed: () {
                                Navigator.pop(context);
                                _showSlotForm(date: date, slot: slot);
                              },
                              icon: const Icon(Icons.edit_outlined),
                              tooltip: 'تعديل',
                            ),
                            IconButton(
                              onPressed: () {
                                setState(() => availabilitySlots.remove(slot));
                                Navigator.pop(context);
                              },
                              icon: const Icon(Icons.delete_outline),
                              tooltip: 'حذف',
                            ),
                          ],
                        ),
                      ),
                  const SizedBox(height: 12),
                  SizedBox(
                    width: double.infinity,
                    child: FilledButton.icon(
                      onPressed: () {
                        Navigator.pop(context);
                        _showSlotForm(date: date);
                      },
                      icon: const Icon(Icons.add),
                      label: const Text('إضافة فترة جديدة'),
                    ),
                  ),
                ],
              ),
            ),
          ),
        );
      },
    );
  }

  Future<void> _showSlotForm({
    required DateTime date,
    AvailabilitySlotModel? slot,
  }) async {
    final nameController = TextEditingController(
      text: slot?.slotName ?? _suggestedSlotName(),
    );
    final priceOverrideController = TextEditingController(
      text: slot?.price ?? '',
    );
    var status = slot?.status ?? 'available';
    var startTime = slot?.startTime;
    var endTime = slot?.endTime;
    var applyMode = 'day';

    final saved = await showModalBottomSheet<bool>(
      context: context,
      isScrollControlled: true,
      builder: (context) {
        return Directionality(
          textDirection: TextDirection.rtl,
          child: StatefulBuilder(
            builder: (context, sheetSetState) {
              Future<void> pickTime(bool isStart) async {
                final picked = await showTimePicker(
                  context: context,
                  initialTime: _timeFromText(isStart ? startTime : endTime),
                );
                if (picked == null) return;
                final text =
                    '${picked.hour.toString().padLeft(2, '0')}:${picked.minute.toString().padLeft(2, '0')}';
                sheetSetState(() {
                  if (isStart) {
                    startTime = text;
                  } else {
                    endTime = text;
                  }
                });
              }

              return SafeArea(
                child: Padding(
                  padding: EdgeInsets.fromLTRB(
                    16,
                    16,
                    16,
                    MediaQuery.viewInsetsOf(context).bottom + 16,
                  ),
                  child: SingleChildScrollView(
                    child: Column(
                      mainAxisSize: MainAxisSize.min,
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          slot == null ? 'إضافة فترة' : 'تعديل الفترة',
                          style: Theme.of(context).textTheme.titleLarge,
                        ),
                        const SizedBox(height: 12),
                        TextField(
                          controller: nameController,
                          decoration: const InputDecoration(
                            labelText: 'اسم الفترة',
                            prefixIcon: Icon(Icons.label_outline),
                          ),
                        ),
                        const SizedBox(height: 12),
                        Row(
                          children: [
                            Expanded(
                              child: OutlinedButton.icon(
                                onPressed: () => pickTime(true),
                                icon: const Icon(Icons.access_time),
                                label: Text(startTime ?? 'وقت البداية'),
                              ),
                            ),
                            const SizedBox(width: 10),
                            Expanded(
                              child: OutlinedButton.icon(
                                onPressed: () => pickTime(false),
                                icon: const Icon(Icons.access_time_filled),
                                label: Text(endTime ?? 'وقت النهاية'),
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 12),
                        TextField(
                          controller: priceOverrideController,
                          keyboardType: TextInputType.number,
                          decoration: const InputDecoration(
                            labelText: 'سعر الفترة اختياري',
                            prefixIcon: Icon(Icons.payments_outlined),
                          ),
                        ),
                        const SizedBox(height: 12),
                        DropdownButtonFormField<String>(
                          initialValue: status,
                          decoration: const InputDecoration(
                            labelText: 'الحالة',
                            prefixIcon: Icon(Icons.flag_outlined),
                          ),
                          items: const [
                            DropdownMenuItem(
                              value: 'available',
                              child: Text('متاحة'),
                            ),
                            DropdownMenuItem(
                              value: 'reserved',
                              child: Text('محجوزة'),
                            ),
                            DropdownMenuItem(
                              value: 'pending',
                              child: Text('قيد المراجعة'),
                            ),
                            DropdownMenuItem(
                              value: 'unavailable',
                              child: Text('غير متاحة'),
                            ),
                          ],
                          onChanged: (value) {
                            if (value == null) return;
                            sheetSetState(() => status = value);
                          },
                        ),
                        if (slot == null) ...[
                          const SizedBox(height: 12),
                          DropdownButtonFormField<String>(
                            initialValue: applyMode,
                            decoration: const InputDecoration(
                              labelText: 'تطبيق الفترة على',
                              prefixIcon: Icon(Icons.event_repeat_outlined),
                            ),
                            items: const [
                              DropdownMenuItem(
                                value: 'day',
                                child: Text('هذا اليوم فقط'),
                              ),
                              DropdownMenuItem(
                                value: 'week',
                                child: Text('كل أيام هذا الأسبوع'),
                              ),
                              DropdownMenuItem(
                                value: 'month',
                                child: Text('كل أيام هذا الشهر'),
                              ),
                            ],
                            onChanged: (value) {
                              if (value == null) return;
                              sheetSetState(() => applyMode = value);
                            },
                          ),
                        ],
                        const SizedBox(height: 18),
                        SizedBox(
                          width: double.infinity,
                          child: FilledButton.icon(
                            onPressed: () => Navigator.pop(context, true),
                            icon: const Icon(Icons.save_outlined),
                            label: const Text('حفظ الفترة'),
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
              );
            },
          ),
        );
      },
    );

    if (saved != true) {
      nameController.dispose();
      priceOverrideController.dispose();
      return;
    }

    final template = AvailabilitySlotModel(
      id: slot?.id ?? _localSlotId--,
      listingId: editingListing?.id ?? 0,
      date: DateUtils.dateOnly(date),
      slotName: nameController.text.trim().isEmpty
          ? 'فترة خاصة'
          : nameController.text.trim(),
      startTime: startTime,
      endTime: endTime,
      price: priceOverrideController.text.trim().isEmpty
          ? null
          : priceOverrideController.text.trim(),
      status: status,
    );
    nameController.dispose();
    priceOverrideController.dispose();

    setState(() {
      if (slot != null) {
        final index = availabilitySlots.indexOf(slot);
        if (index >= 0) {
          availabilitySlots[index] = template;
        }
      } else {
        for (final targetDate in _datesForApplyMode(date, applyMode)) {
          availabilitySlots.add(
            template.copyWith(id: _localSlotId--, date: targetDate),
          );
        }
      }
      availabilitySlots.sort((a, b) {
        final byDate = a.date.compareTo(b.date);
        if (byDate != 0) return byDate;
        return (a.startTime ?? '').compareTo(b.startTime ?? '');
      });
    });
  }

  String _suggestedSlotName() {
    final slug = selectedCategory?.slug;
    return switch (slug) {
      'sports-fields' => 'ساعة حجز',
      'wedding-halls' => 'فترة مناسبة',
      'hotels' => 'ليلة كاملة',
      'chalets' => 'يوم كامل',
      _ => 'فترة خاصة',
    };
  }

  List<DateTime> _datesForApplyMode(DateTime date, String mode) {
    final selected = DateUtils.dateOnly(date);
    if (mode == 'week') {
      final start = selected.subtract(Duration(days: selected.weekday % 7));
      return List.generate(7, (index) => start.add(Duration(days: index)));
    }
    if (mode == 'month') {
      final last = DateTime(selected.year, selected.month + 1, 0);
      return List.generate(
        last.day,
        (index) => DateTime(selected.year, selected.month, index + 1),
      );
    }
    return [selected];
  }

  TimeOfDay _timeFromText(String? value) {
    final parts = (value ?? '').split(':');
    if (parts.length != 2) return const TimeOfDay(hour: 9, minute: 0);
    return TimeOfDay(
      hour: int.tryParse(parts[0]) ?? 9,
      minute: int.tryParse(parts[1]) ?? 0,
    );
  }

  Future<void> _save() async {
    if (!formKey.currentState!.validate()) {
      setState(() => currentStep = 0);
      return;
    }

    final category = selectedCategory;
    if (category == null) {
      setState(() {
        currentStep = 0;
        error = 'اختر القسم أولا.';
      });
      return;
    }

    setState(() {
      isSaving = true;
      error = null;
    });

    final payload = <String, dynamic>{
      'category_id': category.id,
      'title_ar': titleController.text.trim(),
      'description_ar': descriptionController.text.trim(),
      'area_name_ar': areaController.text.trim(),
      'address': addressController.text.trim(),
      'phone': phoneController.text.trim(),
      'whatsapp': whatsappController.text.trim(),
      'currency_code': 'JOD',
      'price_unit': priceUnit,
      'status': status,
      if (priceController.text.trim().isNotEmpty)
        'price': priceController.text.trim(),
      if (latitudeController.text.trim().isNotEmpty)
        'latitude': latitudeController.text.trim(),
      if (longitudeController.text.trim().isNotEmpty)
        'longitude': longitudeController.text.trim(),
      if (attributes.isNotEmpty)
        'attributes': Map<String, dynamic>.from(attributes),
      if (CategoryPresentation.showsAvailability(category))
        'availability_slots': availabilitySlots
            .map((slot) => slot.copyWith(id: 0).toJson())
            .toList(),
    };

    try {
      final repository = Get.find<ListingManagementRepository>();
      if (editingListing == null) {
        await repository.createListing(payload, mediaFiles: selectedMediaFiles);
      } else {
        await repository.updateListing(
          editingListing!,
          payload,
          mediaFiles: selectedMediaFiles,
        );
      }

      if (Get.isRegistered<MyListingsController>()) {
        await Get.find<MyListingsController>().loadAll();
      }

      Get.offNamed(AppRoutes.myListings);
    } on DioException catch (exception) {
      setState(() {
        currentStep = 4;
        error = _message(exception);
      });
    } catch (_) {
      setState(() {
        currentStep = 4;
        error = 'تعذر حفظ الإعلان.';
      });
    } finally {
      if (mounted) setState(() => isSaving = false);
    }
  }

  void _prefill(ListingModel? listing) {
    if (listing == null) return;

    selectedCategory = listing.category;
    titleController.text = listing.titleAr;
    descriptionController.text = listing.descriptionAr ?? '';
    priceController.text = listing.basePrice ?? '';
    areaController.text = listing.areaName ?? '';
    addressController.text = listing.address ?? '';
    phoneController.text = listing.phone ?? '';
    whatsappController.text = listing.whatsapp ?? '';
    latitudeController.text = listing.latitude?.toString() ?? '';
    longitudeController.text = listing.longitude?.toString() ?? '';
    priceUnit = listing.priceUnit ?? 'day';
    status = listing.status == 'pending' ? 'pending' : 'active';

    attributes.addEntries(
      listing.attributes.map(
        (attribute) => MapEntry(attribute.key, attribute.value),
      ),
    );
    calendarDates.addAll(listing.calendarDates);
    availabilitySlots.addAll(
      listing.availabilitySlots.isNotEmpty
          ? listing.availabilitySlots
          : listing.calendarDates.map(
              (date) => AvailabilitySlotModel(
                id: _localSlotId--,
                listingId: listing.id,
                date: date.date,
                slotName: 'يوم كامل',
                status: switch (date.status) {
                  'booked' => 'reserved',
                  'blocked' => 'unavailable',
                  _ => 'available',
                },
                price: date.priceOverride,
              ),
            ),
    );
  }

  String? _required(String? value) {
    return value == null || value.trim().isEmpty ? 'هذا الحقل مطلوب' : null;
  }

  String _message(DioException exception) {
    final data = exception.response?.data;
    if (data is Map<String, dynamic>) {
      final message = data['message'];
      if (message is String && message.isNotEmpty) return message;
      final errors = data['errors'];
      if (errors is Map && errors.isNotEmpty) {
        final first = errors.values.first;
        if (first is List && first.isNotEmpty) return '${first.first}';
      }
    }

    return 'تعذر الاتصال بالخادم.';
  }
}

class _BasicStep extends StatelessWidget {
  const _BasicStep({
    required this.titleController,
    required this.descriptionController,
    required this.areaController,
    required this.addressController,
    required this.phoneController,
    required this.whatsappController,
    required this.latitudeController,
    required this.longitudeController,
    required this.onUseCurrentLocation,
    required this.categories,
    required this.selectedCategory,
    required this.onCategoryChanged,
    required this.requiredValidator,
  });

  final TextEditingController titleController;
  final TextEditingController descriptionController;
  final TextEditingController areaController;
  final TextEditingController addressController;
  final TextEditingController phoneController;
  final TextEditingController whatsappController;
  final TextEditingController latitudeController;
  final TextEditingController longitudeController;
  final Future<void> Function() onUseCurrentLocation;
  final List<CategoryModel> categories;
  final CategoryModel? selectedCategory;
  final ValueChanged<int?> onCategoryChanged;
  final FormFieldValidator<String> requiredValidator;

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        DropdownButtonFormField<int>(
          initialValue: selectedCategory?.id,
          decoration: const InputDecoration(
            labelText: 'القسم',
            prefixIcon: Icon(Icons.category_outlined),
          ),
          items: [
            for (final category in categories)
              DropdownMenuItem(
                value: category.id,
                child: Text(category.nameAr),
              ),
          ],
          onChanged: categories.length == 1 ? null : onCategoryChanged,
          validator: (value) => value == null ? 'اختر القسم' : null,
        ),
        const SizedBox(height: 12),
        TextFormField(
          controller: titleController,
          decoration: const InputDecoration(
            labelText: 'عنوان الإعلان',
            prefixIcon: Icon(Icons.title_outlined),
          ),
          validator: requiredValidator,
        ),
        const SizedBox(height: 12),
        TextFormField(
          controller: areaController,
          decoration: const InputDecoration(
            labelText: 'المدينة / المنطقة',
            prefixIcon: Icon(Icons.place_outlined),
          ),
        ),
        const SizedBox(height: 12),
        TextFormField(
          controller: addressController,
          decoration: const InputDecoration(
            labelText: 'العنوان',
            prefixIcon: Icon(Icons.map_outlined),
          ),
        ),
        const SizedBox(height: 12),
        TextFormField(
          controller: descriptionController,
          minLines: 3,
          maxLines: 5,
          decoration: const InputDecoration(
            labelText: 'الوصف',
            prefixIcon: Icon(Icons.notes_outlined),
          ),
        ),
        const SizedBox(height: 12),
        Row(
          children: [
            Expanded(
              child: TextFormField(
                controller: phoneController,
                keyboardType: TextInputType.phone,
                decoration: const InputDecoration(labelText: 'الهاتف'),
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: TextFormField(
                controller: whatsappController,
                keyboardType: TextInputType.phone,
                decoration: const InputDecoration(labelText: 'واتساب'),
              ),
            ),
          ],
        ),
        const SizedBox(height: 12),
        Row(
          children: [
            Expanded(
              child: TextFormField(
                controller: latitudeController,
                keyboardType: TextInputType.number,
                decoration: const InputDecoration(labelText: 'Latitude'),
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: TextFormField(
                controller: longitudeController,
                keyboardType: TextInputType.number,
                decoration: const InputDecoration(labelText: 'Longitude'),
              ),
            ),
          ],
        ),
        const SizedBox(height: 10),
        Align(
          alignment: AlignmentDirectional.centerStart,
          child: OutlinedButton.icon(
            onPressed: onUseCurrentLocation,
            icon: const Icon(Icons.my_location_outlined),
            label: const Text('استخدام موقعي الحالي'),
          ),
        ),
      ],
    );
  }
}

class _PriceDetailsStep extends StatelessWidget {
  const _PriceDetailsStep({
    required this.priceController,
    required this.priceUnit,
    required this.selectedCategory,
    required this.attributes,
    required this.onPriceUnitChanged,
    required this.onAttributeChanged,
  });

  final TextEditingController priceController;
  final String priceUnit;
  final CategoryModel? selectedCategory;
  final Map<String, dynamic> attributes;
  final ValueChanged<String?> onPriceUnitChanged;
  final void Function(String key, dynamic value) onAttributeChanged;

  @override
  Widget build(BuildContext context) {
    final filters = selectedCategory?.filters ?? const <CategoryFilterModel>[];

    return Column(
      children: [
        Row(
          children: [
            Expanded(
              child: TextFormField(
                controller: priceController,
                keyboardType: TextInputType.number,
                decoration: const InputDecoration(
                  labelText: 'السعر',
                  prefixIcon: Icon(Icons.payments_outlined),
                ),
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: DropdownButtonFormField<String>(
                initialValue: priceUnit,
                decoration: const InputDecoration(labelText: 'نوع السعر'),
                items: const [
                  DropdownMenuItem(value: 'day', child: Text('يوم')),
                  DropdownMenuItem(value: 'night', child: Text('ليلة')),
                  DropdownMenuItem(value: 'hour', child: Text('ساعة')),
                  DropdownMenuItem(value: 'trip', child: Text('رحلة')),
                  DropdownMenuItem(value: 'person', child: Text('شخص')),
                  DropdownMenuItem(value: 'month', child: Text('شهر')),
                  DropdownMenuItem(value: 'product', child: Text('منتج')),
                ],
                onChanged: onPriceUnitChanged,
              ),
            ),
          ],
        ),
        if (filters.isNotEmpty) ...[
          const SizedBox(height: 16),
          Align(
            alignment: AlignmentDirectional.centerStart,
            child: Text(
              'تفاصيل حسب القسم',
              style: Theme.of(context).textTheme.titleMedium,
            ),
          ),
          const SizedBox(height: 10),
          for (final filter in filters) ...[
            _DynamicAttributeField(
              filter: filter,
              value: attributes[filter.key],
              onChanged: (value) => onAttributeChanged(filter.key, value),
            ),
            const SizedBox(height: 12),
          ],
        ],
      ],
    );
  }
}

class _DynamicAttributeField extends StatelessWidget {
  const _DynamicAttributeField({
    required this.filter,
    required this.value,
    required this.onChanged,
  });

  final CategoryFilterModel filter;
  final dynamic value;
  final ValueChanged<dynamic> onChanged;

  @override
  Widget build(BuildContext context) {
    if (filter.inputType == 'boolean') {
      return SwitchListTile(
        value: value == true || value == 'true' || value == 1,
        onChanged: onChanged,
        title: Text(filter.labelAr),
        contentPadding: EdgeInsets.zero,
      );
    }

    if (filter.options.isNotEmpty || filter.inputType == 'select') {
      return DropdownButtonFormField<String>(
        initialValue: value?.toString().isEmpty == false
            ? value.toString()
            : null,
        decoration: InputDecoration(labelText: filter.labelAr),
        items: [
          for (final option in filter.options)
            DropdownMenuItem(value: option.value, child: Text(option.labelAr)),
        ],
        onChanged: onChanged,
      );
    }

    return TextFormField(
      initialValue: value?.toString() ?? '',
      keyboardType: filter.inputType == 'number' || filter.inputType == 'rating'
          ? TextInputType.number
          : TextInputType.text,
      decoration: InputDecoration(
        labelText: filter.labelAr,
        suffixText: filter.unitAr,
      ),
      onChanged: onChanged,
    );
  }
}

class _ImagesStep extends StatelessWidget {
  const _ImagesStep({
    required this.selectedFiles,
    required this.existingMedia,
    required this.onPick,
    required this.onRemove,
  });

  final List<PlatformFile> selectedFiles;
  final List<ListingMediaModel> existingMedia;
  final Future<void> Function() onPick;
  final ValueChanged<PlatformFile> onRemove;

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        OutlinedButton.icon(
          onPressed: onPick,
          icon: const Icon(Icons.add_photo_alternate_outlined),
          label: const Text('رفع صور أو فيديو'),
        ),
        const SizedBox(height: 12),
        if (existingMedia.isEmpty && selectedFiles.isEmpty)
          Text(
            'لم تتم إضافة صور بعد.',
            style: Theme.of(context).textTheme.bodySmall,
          )
        else
          Wrap(
            spacing: 10,
            runSpacing: 10,
            children: [
              for (final media in existingMedia)
                _MediaPreview(
                  name: media.mediaType == 'video' ? 'فيديو' : 'صورة',
                ),
              for (final file in selectedFiles)
                _MediaPreview(name: file.name, onRemove: () => onRemove(file)),
            ],
          ),
      ],
    );
  }
}

class _MediaPreview extends StatelessWidget {
  const _MediaPreview({required this.name, this.onRemove});

  final String name;
  final VoidCallback? onRemove;

  @override
  Widget build(BuildContext context) {
    return DecoratedBox(
      decoration: BoxDecoration(
        color: Colors.white,
        border: Border.all(color: AppTheme.border),
        borderRadius: BorderRadius.circular(8),
      ),
      child: SizedBox(
        width: 138,
        child: Padding(
          padding: const EdgeInsets.all(10),
          child: Column(
            children: [
              const Icon(
                Icons.image_outlined,
                size: 36,
                color: AppTheme.primary,
              ),
              const SizedBox(height: 6),
              Text(name, maxLines: 1, overflow: TextOverflow.ellipsis),
              if (onRemove != null)
                TextButton.icon(
                  onPressed: onRemove,
                  icon: const Icon(Icons.close, size: 16),
                  label: const Text('إزالة'),
                ),
            ],
          ),
        ),
      ),
    );
  }
}

class _AvailabilityStep extends StatelessWidget {
  const _AvailabilityStep({
    required this.enabled,
    required this.slots,
    required this.paintStatus,
    required this.onPaintStatusChanged,
    required this.onDayTap,
    required this.onAvailableAll,
    required this.onUnavailableAll,
    required this.onClear,
  });

  final bool enabled;
  final List<AvailabilitySlotModel> slots;
  final String? paintStatus;
  final ValueChanged<String?> onPaintStatusChanged;
  final ValueChanged<DateTime> onDayTap;
  final VoidCallback onAvailableAll;
  final VoidCallback onUnavailableAll;
  final VoidCallback onClear;

  @override
  Widget build(BuildContext context) {
    if (!enabled) {
      return const Text('هذا القسم لا يحتاج تقويم توفر.');
    }

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          paintStatus == null
              ? 'اضغط على أي يوم لعرض فتراته أو إضافة فترة مخصصة.'
              : 'اضغط على الأيام في التقويم لتطبيق الحالة المختارة مباشرة.',
          style: Theme.of(context).textTheme.bodySmall,
        ),
        const SizedBox(height: 10),
        AvailabilityCalendar(slots: slots, compact: true, onDayTap: onDayTap),
        const SizedBox(height: 12),
        Wrap(
          spacing: 8,
          runSpacing: 8,
          children: [
            FilledButton.icon(
              onPressed: onAvailableAll,
              icon: const Icon(Icons.event_available_outlined),
              label: const Text('متاح كل الأيام'),
            ),
            OutlinedButton.icon(
              onPressed: () => onPaintStatusChanged(
                paintStatus == 'available' ? null : 'available',
              ),
              icon: const Icon(Icons.add),
              label: const Text('إضافة فترة متاحة'),
            ),
            OutlinedButton.icon(
              onPressed: () => onPaintStatusChanged(
                paintStatus == 'reserved' ? null : 'reserved',
              ),
              icon: const Icon(Icons.block),
              label: const Text('إضافة فترة محجوزة'),
            ),
            OutlinedButton.icon(
              onPressed: onUnavailableAll,
              icon: const Icon(Icons.event_busy_outlined),
              label: const Text('غير متاح كل الأيام'),
            ),
            TextButton.icon(
              onPressed: onClear,
              icon: const Icon(Icons.cleaning_services_outlined),
              label: const Text('مسح التحديد'),
            ),
          ],
        ),
      ],
    );
  }
}

class _ReviewStep extends StatelessWidget {
  const _ReviewStep({
    required this.title,
    required this.category,
    required this.area,
    required this.price,
    required this.priceUnit,
    required this.mediaCount,
    required this.datesCount,
    required this.error,
    required this.isSaving,
  });

  final String title;
  final CategoryModel? category;
  final String area;
  final String price;
  final String priceUnit;
  final int mediaCount;
  final int datesCount;
  final String? error;
  final bool isSaving;

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        _SummaryRow(label: 'العنوان', value: title),
        _SummaryRow(label: 'القسم', value: category?.nameAr ?? '-'),
        _SummaryRow(label: 'المنطقة', value: area.isEmpty ? '-' : area),
        _SummaryRow(
          label: 'السعر',
          value: price.isEmpty ? 'عند التواصل' : price,
        ),
        _SummaryRow(label: 'الصور والفيديو', value: '$mediaCount ملف'),
        _SummaryRow(label: 'أيام التوفر', value: '$datesCount يوم'),
        if (error != null) ...[
          const SizedBox(height: 12),
          DecoratedBox(
            decoration: BoxDecoration(
              color: const Color(0xFFFFF1F0),
              borderRadius: BorderRadius.circular(8),
            ),
            child: Padding(
              padding: const EdgeInsets.all(12),
              child: Text(error!, style: const TextStyle(color: Colors.red)),
            ),
          ),
        ],
        if (isSaving) const LinearProgressIndicator(),
      ],
    );
  }
}

class _SummaryRow extends StatelessWidget {
  const _SummaryRow({required this.label, required this.value});

  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: Row(
        children: [
          SizedBox(
            width: 120,
            child: Text(label, style: Theme.of(context).textTheme.bodySmall),
          ),
          Expanded(
            child: Text(value, style: Theme.of(context).textTheme.titleSmall),
          ),
        ],
      ),
    );
  }
}
