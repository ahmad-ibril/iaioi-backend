import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../core/config/app_theme.dart';
import '../../data/repositories/admin_repository.dart';
import '../../routes/app_routes.dart';
import '../../shared/widgets/empty_state.dart';
import '../auth/auth_controller.dart';

class AdminDashboardPage extends StatefulWidget {
  const AdminDashboardPage({super.key});

  @override
  State<AdminDashboardPage> createState() => _AdminDashboardPageState();
}

class _AdminDashboardPageState extends State<AdminDashboardPage> {
  final _sections = const [
    _AdminSection('overview', 'الرئيسية', Icons.dashboard_outlined),
    _AdminSection('listings', 'الإعلانات', Icons.campaign_outlined),
    _AdminSection('categories', 'الأقسام', Icons.category_outlined),
    _AdminSection('users', 'المستخدمون', Icons.group_outlined),
    _AdminSection('bookings', 'الحجوزات', Icons.receipt_long_outlined),
    _AdminSection('availability', 'التوفر', Icons.event_available_outlined),
    _AdminSection('locations', 'المدن', Icons.location_city_outlined),
    _AdminSection('ads', 'الإعلانات المدفوعة', Icons.ads_click_outlined),
    _AdminSection('settings', 'الإعدادات', Icons.settings_outlined),
    _AdminSection('banners', 'البانرات', Icons.image_outlined),
  ];

  var _selected = 'overview';
  var _isLoading = true;
  String? _error;

  Map<String, dynamic> _dashboard = const {};
  Map<String, dynamic> _settings = const {};
  List<Map<String, dynamic>> _listings = const [];
  List<Map<String, dynamic>> _categories = const [];
  List<Map<String, dynamic>> _users = const [];
  List<Map<String, dynamic>> _bookings = const [];
  List<Map<String, dynamic>> _slots = const [];
  List<Map<String, dynamic>> _cities = const [];
  List<Map<String, dynamic>> _areas = const [];
  List<Map<String, dynamic>> _banners = const [];

  AdminRepository get _repo => Get.find<AdminRepository>();

  @override
  void initState() {
    super.initState();
    Future.microtask(_load);
  }

  @override
  Widget build(BuildContext context) {
    final auth = Get.find<UserAuthController>();

    return Scaffold(
      appBar: AppBar(
        title: const Text('لوحة تحكم الأدمن'),
        actions: [
          IconButton(
            onPressed: _load,
            icon: const Icon(Icons.refresh),
            tooltip: 'تحديث',
          ),
        ],
      ),
      body: Obx(() {
        final user = auth.user.value;
        if (!auth.isAuthenticated) {
          return EmptyState(
            message: 'سجل الدخول بحساب أدمن لفتح لوحة التحكم.',
            actionLabel: 'تسجيل الدخول',
            onRetry: () => Get.toNamed(AppRoutes.login),
          );
        }

        if (user?.role != 'admin') {
          return const EmptyState(message: 'هذه الصفحة مخصصة للأدمن فقط.');
        }

        if (_isLoading) return const Center(child: CircularProgressIndicator());
        if (_error != null) return EmptyState(message: _error!, onRetry: _load);

        final wide = MediaQuery.sizeOf(context).width >= 900;
        return wide
            ? Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  _SideNav(
                    sections: _sections,
                    selected: _selected,
                    onSelected: (value) => setState(() => _selected = value),
                  ),
                  Expanded(child: _content()),
                ],
              )
            : Column(
                children: [
                  _TopNav(
                    sections: _sections,
                    selected: _selected,
                    onSelected: (value) => setState(() => _selected = value),
                  ),
                  Expanded(child: _content()),
                ],
              );
      }),
    );
  }

  Widget _content() {
    return RefreshIndicator(
      onRefresh: _load,
      child: Center(
        child: ConstrainedBox(
          constraints: const BoxConstraints(maxWidth: 1040),
          child: ListView(
            padding: const EdgeInsets.all(16),
            children: [
              switch (_selected) {
                'listings' => _listingsSection(),
                'categories' => _categoriesSection(),
                'users' => _usersSection(),
                'bookings' => _bookingsSection(),
                'availability' => _availabilitySection(),
                'locations' => _locationsSection(),
                'ads' => _adsSection(),
                'settings' => _settingsSection(),
                'banners' => _bannersSection(),
                _ => _overviewSection(),
              },
            ],
          ),
        ),
      ),
    );
  }

  Future<void> _load() async {
    setState(() {
      _isLoading = true;
      _error = null;
    });

    try {
      final result = await Future.wait([
        _repo.dashboard(),
        _repo.listings(),
        _repo.categories(),
        _repo.users(),
        _repo.bookings(),
        _repo.availabilitySlots(),
        _repo.cities(),
        _repo.areas(),
        _repo.settings(),
        _repo.banners(),
      ]);

      setState(() {
        _dashboard = result[0] as Map<String, dynamic>;
        _listings = result[1] as List<Map<String, dynamic>>;
        _categories = result[2] as List<Map<String, dynamic>>;
        _users = result[3] as List<Map<String, dynamic>>;
        _bookings = result[4] as List<Map<String, dynamic>>;
        _slots = result[5] as List<Map<String, dynamic>>;
        _cities = result[6] as List<Map<String, dynamic>>;
        _areas = result[7] as List<Map<String, dynamic>>;
        _settings = result[8] as Map<String, dynamic>;
        _banners = result[9] as List<Map<String, dynamic>>;
      });
    } catch (_) {
      setState(() => _error = 'تعذر تحميل بيانات لوحة التحكم.');
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  Widget _overviewSection() {
    final stats = _map(_dashboard['stats']);
    final cards = [
      ('عدد المستخدمين', stats['users'], Icons.group_outlined),
      ('عدد الإعلانات', stats['listings'], Icons.campaign_outlined),
      ('عدد الحجوزات', stats['bookings'], Icons.event_available_outlined),
      ('طلبات الحجز', stats['booking_requests'], Icons.receipt_outlined),
      ('إعلانات نشطة', stats['active_listings'], Icons.check_circle_outline),
      ('قيد المراجعة', stats['pending_listings'], Icons.hourglass_top_outlined),
    ];

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        _SectionHeader(title: 'نظرة عامة', subtitle: 'إحصائيات التطبيق'),
        _StatsGrid(cards: cards),
        const SizedBox(height: 16),
        _SectionHeader(title: 'إجراءات سريعة'),
        Wrap(
          spacing: 10,
          runSpacing: 10,
          children: [
            FilledButton.icon(
              onPressed: _showListingDialog,
              icon: const Icon(Icons.add),
              label: const Text('إضافة إعلان'),
            ),
            OutlinedButton.icon(
              onPressed: _showCategoryDialog,
              icon: const Icon(Icons.category_outlined),
              label: const Text('إضافة قسم'),
            ),
            OutlinedButton.icon(
              onPressed: _showBannerDialog,
              icon: const Icon(Icons.image_outlined),
              label: const Text('إضافة بانر'),
            ),
          ],
        ),
      ],
    );
  }

  Widget _listingsSection() {
    return _Panel(
      title: 'إدارة الإعلانات',
      action: FilledButton.icon(
        onPressed: _showListingDialog,
        icon: const Icon(Icons.add),
        label: const Text('إضافة إعلان'),
      ),
      children: [
        for (final listing in _listings)
          _AdminCard(
            title: listing['title_ar'] ?? 'إعلان بدون عنوان',
            subtitle:
                '${_nested(listing, 'category', 'name_ar') ?? 'بدون قسم'} - ${listing['status'] ?? '-'}',
            leading: listing['is_featured'] == true
                ? Icons.workspace_premium_outlined
                : Icons.campaign_outlined,
            badge: listing['is_featured'] == true ? 'مميز' : null,
            actions: [
              _MenuAction(
                label: 'الحالة',
                icon: Icons.flag_outlined,
                children: {
                  'active': 'نشط',
                  'pending': 'قيد المراجعة',
                  'rejected': 'مرفوض',
                  'expired': 'منتهي',
                  'inactive': 'غير نشط',
                },
                onSelected: (status) =>
                    _updateListingStatus(_id(listing), {'status': status}),
              ),
              IconButton(
                onPressed: () => _showListingDialog(listing),
                icon: const Icon(Icons.edit_outlined),
                tooltip: 'تعديل',
              ),
              IconButton(
                onPressed: () => _toggleFeatured(listing),
                icon: const Icon(Icons.star_border),
                tooltip: 'تمييز',
              ),
              IconButton(
                onPressed: () =>
                    _delete(() => _repo.deleteListing(_id(listing))),
                icon: const Icon(Icons.delete_outline),
                tooltip: 'حذف',
              ),
            ],
          ),
      ],
    );
  }

  Widget _categoriesSection() {
    return _Panel(
      title: 'إدارة الأقسام',
      action: FilledButton.icon(
        onPressed: _showCategoryDialog,
        icon: const Icon(Icons.add),
        label: const Text('إضافة قسم'),
      ),
      children: [
        for (final category in _categories)
          _AdminCard(
            title: category['name_ar'] ?? '',
            subtitle:
                'الأيقونة: ${category['icon'] ?? '-'} - الترتيب: ${category['sort_order'] ?? 0}',
            leading: Icons.category_outlined,
            actions: [
              IconButton(
                onPressed: () => _showCategoryDialog(category),
                icon: const Icon(Icons.edit_outlined),
              ),
              IconButton(
                onPressed: () =>
                    _delete(() => _repo.deleteCategory(_id(category))),
                icon: const Icon(Icons.delete_outline),
              ),
            ],
          ),
      ],
    );
  }

  Widget _usersSection() {
    return _Panel(
      title: 'إدارة المستخدمين',
      children: [
        for (final user in _users)
          _AdminCard(
            title: user['name'] ?? '',
            subtitle:
                '${user['email'] ?? user['phone'] ?? ''} - ${user['role'] ?? 'customer'} - ${user['status'] ?? '-'}',
            leading: Icons.person_outline,
            actions: [
              _MenuAction(
                label: 'الدور',
                icon: Icons.admin_panel_settings_outlined,
                children: const {
                  'admin': 'admin',
                  'owner': 'owner',
                  'customer': 'customer',
                },
                onSelected: (role) => _updateUser(_id(user), {'role': role}),
              ),
              _MenuAction(
                label: 'الحالة',
                icon: Icons.power_settings_new,
                children: const {'active': 'تفعيل', 'inactive': 'تعطيل'},
                onSelected: (status) =>
                    _updateUser(_id(user), {'status': status}),
              ),
              IconButton(
                onPressed: () => _delete(() => _repo.deleteUser(_id(user))),
                icon: const Icon(Icons.delete_outline),
              ),
            ],
          ),
      ],
    );
  }

  Widget _bookingsSection() {
    return _Panel(
      title: 'إدارة الحجوزات',
      children: [
        for (final booking in _bookings)
          _AdminCard(
            title: _nested(booking, 'listing', 'title_ar') ?? 'حجز',
            subtitle:
                '${booking['customer_name'] ?? booking['contact_name'] ?? '-'} - ${booking['status_label'] ?? booking['status'] ?? '-'}',
            leading: Icons.receipt_long_outlined,
            actions: [
              IconButton(
                onPressed: () => _bookingAction(_id(booking), accept: true),
                icon: const Icon(Icons.check),
                tooltip: 'قبول',
              ),
              IconButton(
                onPressed: () => _bookingAction(_id(booking), accept: false),
                icon: const Icon(Icons.close),
                tooltip: 'رفض',
              ),
              _MenuAction(
                label: 'تعديل الحالة',
                icon: Icons.flag_outlined,
                children: const {
                  'pending': 'قيد المراجعة',
                  'accepted': 'مقبول',
                  'rejected': 'مرفوض',
                  'cancelled': 'ملغي',
                },
                onSelected: (status) =>
                    _updateBooking(_id(booking), {'status': status}),
              ),
            ],
          ),
      ],
    );
  }

  Widget _availabilitySection() {
    return _Panel(
      title: 'إدارة التوفر والمواعيد',
      action: FilledButton.icon(
        onPressed: _showSlotDialog,
        icon: const Icon(Icons.add),
        label: const Text('إضافة فترة'),
      ),
      children: [
        for (final slot in _slots)
          _AdminCard(
            title: '${slot['slot_name'] ?? 'فترة'} - ${slot['date'] ?? ''}',
            subtitle:
                '${slot['start_time'] ?? 'يوم كامل'} - ${slot['end_time'] ?? ''} - ${slot['status_label'] ?? slot['status'] ?? ''}',
            leading: Icons.event_available_outlined,
            actions: [
              _MenuAction(
                label: 'الحالة',
                icon: Icons.flag_outlined,
                children: const {
                  'available': 'متاح',
                  'reserved': 'محجوز',
                  'pending': 'قيد المراجعة',
                  'unavailable': 'غير متاح',
                },
                onSelected: (status) =>
                    _updateSlot(_id(slot), {'status': status}),
              ),
              IconButton(
                onPressed: () => _showSlotDialog(slot),
                icon: const Icon(Icons.edit_outlined),
              ),
              IconButton(
                onPressed: () => _delete(() => _repo.deleteSlot(_id(slot))),
                icon: const Icon(Icons.delete_outline),
              ),
            ],
          ),
      ],
    );
  }

  Widget _locationsSection() {
    return _Panel(
      title: 'إدارة المدن والمناطق',
      action: Wrap(
        spacing: 8,
        children: [
          FilledButton.icon(
            onPressed: _showCityDialog,
            icon: const Icon(Icons.add),
            label: const Text('مدينة'),
          ),
          OutlinedButton.icon(
            onPressed: _showAreaDialog,
            icon: const Icon(Icons.add_location_alt_outlined),
            label: const Text('منطقة'),
          ),
        ],
      ),
      children: [
        for (final city in _cities)
          _AdminCard(
            title: city['name_ar'] ?? '',
            subtitle:
                'المناطق: ${(city['areas'] as List? ?? []).length} - ${city['is_active'] == true ? 'نشطة' : 'معطلة'}',
            leading: Icons.location_city_outlined,
            actions: [
              IconButton(
                onPressed: () => _showCityDialog(city),
                icon: const Icon(Icons.edit_outlined),
              ),
              IconButton(
                onPressed: () => _delete(() => _repo.deleteCity(_id(city))),
                icon: const Icon(Icons.delete_outline),
              ),
            ],
          ),
        if (_areas.isNotEmpty) ...[
          const SizedBox(height: 12),
          _SectionHeader(title: 'المناطق'),
          for (final area in _areas.take(20))
            _AdminCard(
              title: area['name_ar'] ?? '',
              subtitle: 'city_id: ${area['city_id'] ?? '-'}',
              leading: Icons.place_outlined,
              actions: [
                IconButton(
                  onPressed: () => _showAreaDialog(area),
                  icon: const Icon(Icons.edit_outlined),
                ),
                IconButton(
                  onPressed: () => _delete(() => _repo.deleteArea(_id(area))),
                  icon: const Icon(Icons.delete_outline),
                ),
              ],
            ),
        ],
      ],
    );
  }

  Widget _adsSection() {
    final keys = [
      'admob_enabled',
      'android_ad_unit_id',
      'ios_ad_unit_id',
      'web_ad_placeholder',
      'ad_placements',
    ];
    return _Panel(
      title: 'إدارة Google Ads',
      children: [
        for (final key in keys)
          _SettingRow(
            keyName: key,
            setting: _map(_settings[key]),
            onEdit: () => _showSettingDialog(key, group: 'ads'),
          ),
      ],
    );
  }

  Widget _settingsSection() {
    final keys = [
      'app_name',
      'support_whatsapp',
      'support_email',
      'facebook_url',
      'instagram_url',
      'terms_of_use',
      'privacy_policy',
      'booking_fee',
      'commission_rate',
    ];
    return _Panel(
      title: 'الإعدادات العامة',
      children: [
        for (final key in keys)
          _SettingRow(
            keyName: key,
            setting: _map(_settings[key]),
            onEdit: () => _showSettingDialog(key),
          ),
      ],
    );
  }

  Widget _bannersSection() {
    return _Panel(
      title: 'إدارة الصور والبانرات',
      action: FilledButton.icon(
        onPressed: _showBannerDialog,
        icon: const Icon(Icons.add),
        label: const Text('إضافة بانر'),
      ),
      children: [
        for (final banner in _banners)
          _AdminCard(
            title: banner['title_ar'] ?? '',
            subtitle:
                '${banner['placement'] ?? 'home'} - ${banner['is_active'] == true ? 'نشط' : 'معطل'}',
            leading: Icons.image_outlined,
            actions: [
              IconButton(
                onPressed: () => _showBannerDialog(banner),
                icon: const Icon(Icons.edit_outlined),
              ),
              IconButton(
                onPressed: () => _delete(() => _repo.deleteBanner(_id(banner))),
                icon: const Icon(Icons.delete_outline),
              ),
            ],
          ),
      ],
    );
  }

  Future<void> _showListingDialog([Map<String, dynamic>? listing]) async {
    final result = await _formDialog(
      title: listing == null ? 'إضافة إعلان' : 'تعديل إعلان',
      initial: listing ?? const {},
      fields: [
        _FieldSpec('title_ar', 'عنوان الإعلان'),
        _FieldSpec('category_id', 'ID القسم', keyboard: TextInputType.number),
        _FieldSpec('base_price', 'السعر', keyboard: TextInputType.number),
        _FieldSpec('status', 'الحالة'),
      ],
    );
    if (result == null) return;

    result['category_id'] = int.tryParse('${result['category_id'] ?? ''}');
    result['base_price'] = double.tryParse('${result['base_price'] ?? ''}');
    result['currency_code'] = 'JOD';
    result['price_unit'] = 'day';

    if (listing == null) {
      await _repo.createListing(result);
    } else {
      await _repo.updateListing(_id(listing), result);
    }
    await _load();
  }

  Future<void> _showCategoryDialog([Map<String, dynamic>? category]) async {
    final result = await _formDialog(
      title: category == null ? 'إضافة قسم' : 'تعديل قسم',
      initial: category ?? const {},
      fields: [
        _FieldSpec('name_ar', 'اسم القسم'),
        _FieldSpec('icon', 'الأيقونة'),
        _FieldSpec('sort_order', 'الترتيب', keyboard: TextInputType.number),
      ],
    );
    if (result == null) return;
    result['sort_order'] = int.tryParse('${result['sort_order'] ?? 0}') ?? 0;
    result['supports_booking'] = true;

    if (category == null) {
      await _repo.createCategory(result);
    } else {
      await _repo.updateCategory(_id(category), result);
    }
    await _load();
  }

  Future<void> _showSlotDialog([Map<String, dynamic>? slot]) async {
    final result = await _formDialog(
      title: slot == null ? 'إضافة فترة' : 'تعديل فترة',
      initial: slot ?? const {},
      fields: [
        _FieldSpec('listing_id', 'ID الإعلان', keyboard: TextInputType.number),
        _FieldSpec('date', 'التاريخ YYYY-MM-DD'),
        _FieldSpec('slot_name', 'اسم الفترة'),
        _FieldSpec('start_time', 'وقت البداية HH:mm'),
        _FieldSpec('end_time', 'وقت النهاية HH:mm'),
        _FieldSpec('price', 'السعر', keyboard: TextInputType.number),
        _FieldSpec('status', 'الحالة'),
      ],
    );
    if (result == null) return;
    result['listing_id'] = int.tryParse('${result['listing_id'] ?? ''}');
    result['price'] = double.tryParse('${result['price'] ?? ''}');

    if (slot == null) {
      await _repo.createSlot(result);
    } else {
      await _repo.updateSlot(_id(slot), result);
    }
    await _load();
  }

  Future<void> _showCityDialog([Map<String, dynamic>? city]) async {
    final result = await _formDialog(
      title: city == null ? 'إضافة مدينة' : 'تعديل مدينة',
      initial: city ?? {'country_id': 1},
      fields: [
        _FieldSpec('country_id', 'ID الدولة', keyboard: TextInputType.number),
        _FieldSpec('name_ar', 'اسم المدينة'),
        _FieldSpec('name_en', 'الاسم بالإنجليزية'),
      ],
    );
    if (result == null) return;
    result['country_id'] = int.tryParse('${result['country_id'] ?? 1}') ?? 1;

    if (city == null) {
      await _repo.createCity(result);
    } else {
      await _repo.updateCity(_id(city), result);
    }
    await _load();
  }

  Future<void> _showAreaDialog([Map<String, dynamic>? area]) async {
    final result = await _formDialog(
      title: area == null ? 'إضافة منطقة' : 'تعديل منطقة',
      initial: area ?? const {},
      fields: [
        _FieldSpec('city_id', 'ID المدينة', keyboard: TextInputType.number),
        _FieldSpec('name_ar', 'اسم المنطقة'),
        _FieldSpec('sort_order', 'الترتيب', keyboard: TextInputType.number),
      ],
    );
    if (result == null) return;
    result['city_id'] = int.tryParse('${result['city_id'] ?? ''}');
    result['sort_order'] = int.tryParse('${result['sort_order'] ?? 0}') ?? 0;

    if (area == null) {
      await _repo.createArea(result);
    } else {
      await _repo.updateArea(_id(area), result);
    }
    await _load();
  }

  Future<void> _showBannerDialog([Map<String, dynamic>? banner]) async {
    final result = await _formDialog(
      title: banner == null ? 'إضافة بانر' : 'تعديل بانر',
      initial: banner ?? {'placement': 'home'},
      fields: [
        _FieldSpec('title_ar', 'العنوان'),
        _FieldSpec('subtitle_ar', 'الوصف'),
        _FieldSpec('image_url', 'رابط الصورة'),
        _FieldSpec('link_url', 'الرابط'),
        _FieldSpec('placement', 'المكان'),
        _FieldSpec('sort_order', 'الترتيب', keyboard: TextInputType.number),
      ],
    );
    if (result == null) return;
    result['sort_order'] = int.tryParse('${result['sort_order'] ?? 0}') ?? 0;
    result['is_active'] = true;

    if (banner == null) {
      await _repo.createBanner(result);
    } else {
      await _repo.updateBanner(_id(banner), result);
    }
    await _load();
  }

  Future<void> _showSettingDialog(
    String key, {
    String group = 'general',
  }) async {
    final setting = _map(_settings[key]);
    final result = await _formDialog(
      title: 'تعديل $key',
      initial: {'value': setting['value'] ?? ''},
      fields: [_FieldSpec('value', key, minLines: 1)],
    );
    if (result == null) return;

    await _repo.updateSettings({
      key: {
        'value': result['value'],
        'group': group,
        'value_type': key.contains('enabled') ? 'boolean' : 'string',
      },
    });
    await _load();
  }

  Future<void> _toggleFeatured(Map<String, dynamic> listing) async {
    final isFeatured = listing['is_featured'] == true;
    await _updateListingStatus(_id(listing), {
      'is_featured': !isFeatured,
      'featured_until': !isFeatured
          ? DateTime.now().add(const Duration(days: 30)).toIso8601String()
          : null,
    });
  }

  Future<void> _updateListingStatus(
    int id,
    Map<String, dynamic> payload,
  ) async {
    await _repo.updateListingStatus(id, payload);
    await _load();
  }

  Future<void> _updateUser(int id, Map<String, dynamic> payload) async {
    await _repo.updateUser(id, payload);
    await _load();
  }

  Future<void> _updateBooking(int id, Map<String, dynamic> payload) async {
    await _repo.updateBooking(id, payload);
    await _load();
  }

  Future<void> _updateSlot(int id, Map<String, dynamic> payload) async {
    await _repo.updateSlot(id, payload);
    await _load();
  }

  Future<void> _bookingAction(int id, {required bool accept}) async {
    if (accept) {
      await _repo.acceptBooking(id);
    } else {
      await _repo.rejectBooking(id);
    }
    await _load();
  }

  Future<void> _delete(Future<void> Function() action) async {
    final ok = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('تأكيد الحذف'),
        content: const Text('لا يمكن التراجع عن هذه العملية.'),
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
    if (ok != true) return;
    await action();
    await _load();
  }

  Future<Map<String, dynamic>?> _formDialog({
    required String title,
    required Map<String, dynamic> initial,
    required List<_FieldSpec> fields,
  }) async {
    final controllers = {
      for (final field in fields)
        field.key: TextEditingController(text: '${initial[field.key] ?? ''}'),
    };

    final result = await showDialog<Map<String, dynamic>>(
      context: context,
      builder: (context) => AlertDialog(
        title: Text(title),
        content: SizedBox(
          width: 420,
          child: SingleChildScrollView(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                for (final field in fields) ...[
                  TextField(
                    controller: controllers[field.key],
                    keyboardType: field.keyboard,
                    minLines: field.minLines,
                    maxLines: field.minLines > 1 ? 5 : 1,
                    decoration: InputDecoration(labelText: field.label),
                  ),
                  const SizedBox(height: 10),
                ],
              ],
            ),
          ),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('إلغاء'),
          ),
          FilledButton(
            onPressed: () {
              Navigator.pop(context, {
                for (final entry in controllers.entries)
                  entry.key: entry.value.text.trim(),
              });
            },
            child: const Text('حفظ'),
          ),
        ],
      ),
    );

    for (final controller in controllers.values) {
      controller.dispose();
    }

    return result;
  }
}

class _AdminSection {
  const _AdminSection(this.id, this.label, this.icon);

  final String id;
  final String label;
  final IconData icon;
}

class _FieldSpec {
  const _FieldSpec(
    this.key,
    this.label, {
    this.keyboard = TextInputType.text,
    this.minLines = 1,
  });

  final String key;
  final String label;
  final TextInputType keyboard;
  final int minLines;
}

class _SideNav extends StatelessWidget {
  const _SideNav({
    required this.sections,
    required this.selected,
    required this.onSelected,
  });

  final List<_AdminSection> sections;
  final String selected;
  final ValueChanged<String> onSelected;

  @override
  Widget build(BuildContext context) {
    return DecoratedBox(
      decoration: const BoxDecoration(
        color: Colors.white,
        border: BorderDirectional(end: BorderSide(color: AppTheme.border)),
      ),
      child: SizedBox(
        width: 230,
        child: ListView(
          padding: const EdgeInsets.all(10),
          children: [
            for (final section in sections)
              ListTile(
                selected: selected == section.id,
                leading: Icon(section.icon),
                title: Text(section.label),
                onTap: () => onSelected(section.id),
              ),
          ],
        ),
      ),
    );
  }
}

class _TopNav extends StatelessWidget {
  const _TopNav({
    required this.sections,
    required this.selected,
    required this.onSelected,
  });

  final List<_AdminSection> sections;
  final String selected;
  final ValueChanged<String> onSelected;

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      height: 56,
      child: ListView.separated(
        scrollDirection: Axis.horizontal,
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
        itemBuilder: (context, index) {
          final section = sections[index];
          return ChoiceChip(
            selected: selected == section.id,
            avatar: Icon(section.icon, size: 18),
            label: Text(section.label),
            onSelected: (_) => onSelected(section.id),
          );
        },
        separatorBuilder: (context, index) => const SizedBox(width: 8),
        itemCount: sections.length,
      ),
    );
  }
}

class _StatsGrid extends StatelessWidget {
  const _StatsGrid({required this.cards});

  final List<(String, dynamic, IconData)> cards;

  @override
  Widget build(BuildContext context) {
    return LayoutBuilder(
      builder: (context, constraints) {
        final columns = constraints.maxWidth > 820
            ? 3
            : constraints.maxWidth > 520
            ? 2
            : 1;
        return GridView.builder(
          shrinkWrap: true,
          physics: const NeverScrollableScrollPhysics(),
          itemCount: cards.length,
          gridDelegate: SliverGridDelegateWithFixedCrossAxisCount(
            crossAxisCount: columns,
            crossAxisSpacing: 10,
            mainAxisSpacing: 10,
            childAspectRatio: 2.2,
          ),
          itemBuilder: (context, index) {
            final card = cards[index];
            return _StatCard(label: card.$1, value: card.$2, icon: card.$3);
          },
        );
      },
    );
  }
}

class _StatCard extends StatelessWidget {
  const _StatCard({
    required this.label,
    required this.value,
    required this.icon,
  });

  final String label;
  final dynamic value;
  final IconData icon;

  @override
  Widget build(BuildContext context) {
    return DecoratedBox(
      decoration: BoxDecoration(
        color: Colors.white,
        border: Border.all(color: AppTheme.border),
        borderRadius: BorderRadius.circular(8),
      ),
      child: Padding(
        padding: const EdgeInsets.all(14),
        child: Row(
          children: [
            Icon(icon, color: AppTheme.primaryDark),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Text('$value', style: Theme.of(context).textTheme.titleLarge),
                  Text(label, style: Theme.of(context).textTheme.bodySmall),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _Panel extends StatelessWidget {
  const _Panel({required this.title, this.action, required this.children});

  final String title;
  final Widget? action;
  final List<Widget> children;

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        _SectionHeader(title: title, action: action),
        const SizedBox(height: 10),
        if (children.isEmpty)
          const EmptyState(message: 'لا توجد بيانات حالياً.')
        else
          ...children,
      ],
    );
  }
}

class _SectionHeader extends StatelessWidget {
  const _SectionHeader({required this.title, this.subtitle, this.action});

  final String title;
  final String? subtitle;
  final Widget? action;

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(title, style: Theme.of(context).textTheme.titleLarge),
              if (subtitle != null)
                Text(subtitle!, style: Theme.of(context).textTheme.bodySmall),
            ],
          ),
        ),
        ?action,
      ],
    );
  }
}

class _AdminCard extends StatelessWidget {
  const _AdminCard({
    required this.title,
    required this.subtitle,
    required this.leading,
    this.badge,
    this.actions = const [],
  });

  final String title;
  final String subtitle;
  final IconData leading;
  final String? badge;
  final List<Widget> actions;

  @override
  Widget build(BuildContext context) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(12),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Icon(leading, color: AppTheme.primaryDark),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Expanded(
                        child: Text(
                          title,
                          style: Theme.of(context).textTheme.titleMedium,
                        ),
                      ),
                      if (badge != null) Chip(label: Text(badge!)),
                    ],
                  ),
                  Text(subtitle, style: Theme.of(context).textTheme.bodySmall),
                ],
              ),
            ),
            Wrap(spacing: 2, children: actions),
          ],
        ),
      ),
    );
  }
}

class _MenuAction extends StatelessWidget {
  const _MenuAction({
    required this.label,
    required this.icon,
    required this.children,
    required this.onSelected,
  });

  final String label;
  final IconData icon;
  final Map<String, String> children;
  final ValueChanged<String> onSelected;

  @override
  Widget build(BuildContext context) {
    return PopupMenuButton<String>(
      tooltip: label,
      icon: Icon(icon),
      onSelected: onSelected,
      itemBuilder: (context) => [
        for (final entry in children.entries)
          PopupMenuItem(value: entry.key, child: Text(entry.value)),
      ],
    );
  }
}

class _SettingRow extends StatelessWidget {
  const _SettingRow({
    required this.keyName,
    required this.setting,
    required this.onEdit,
  });

  final String keyName;
  final Map<String, dynamic> setting;
  final VoidCallback onEdit;

  @override
  Widget build(BuildContext context) {
    return Card(
      child: ListTile(
        leading: const Icon(Icons.settings_outlined),
        title: Text(keyName),
        subtitle: Text('${setting['value'] ?? '-'}'),
        trailing: IconButton(
          onPressed: onEdit,
          icon: const Icon(Icons.edit_outlined),
        ),
      ),
    );
  }
}

int _id(Map<String, dynamic> value) => int.tryParse('${value['id'] ?? 0}') ?? 0;

Map<String, dynamic> _map(dynamic value) {
  if (value is Map<String, dynamic>) return value;
  if (value is Map) return Map<String, dynamic>.from(value);
  return const {};
}

dynamic _nested(Map<String, dynamic> value, String key, String child) {
  final nested = value[key];
  if (nested is Map) return nested[child];
  return null;
}
