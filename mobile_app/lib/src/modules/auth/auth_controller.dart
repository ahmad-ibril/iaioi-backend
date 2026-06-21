import 'dart:convert';

import 'package:dio/dio.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:get/get.dart';
import 'package:google_sign_in/google_sign_in.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../../core/config/app_config.dart';
import '../../core/network/api_client.dart';
import '../../data/models/user_model.dart';
import '../../data/repositories/auth_repository.dart';

class UserAuthController extends GetxController {
  UserAuthController(this._repository, this._apiClient);

  static const _tokenKey = 'auth_token';
  static const _secureStorage = FlutterSecureStorage();

  final AuthRepository _repository;
  final ApiClient _apiClient;

  final user = Rxn<UserModel>();
  final token = RxnString();
  final accountTypes = <AccountTypeOption>[].obs;
  final isLoading = false.obs;
  final error = RxnString();
  bool _googleInitialized = false;

  bool get isAuthenticated => token.value != null && user.value != null;

  @override
  void onInit() {
    super.onInit();
    restoreSession();
  }

  Future<void> restoreSession() async {
    isLoading.value = true;
    error.value = null;

    try {
      final prefs = await SharedPreferences.getInstance();
      final savedToken =
          await _secureStorage.read(key: _tokenKey) ??
          prefs.getString(_tokenKey);

      if (savedToken == null || savedToken.isEmpty) return;

      token.value = savedToken;
      _apiClient.setAuthToken(savedToken);
      user.value = await _repository.me();
    } catch (_) {
      await clearSession();
    } finally {
      isLoading.value = false;
    }
  }

  Future<bool> login({required String email, required String password}) async {
    return _authenticate(
      () => _repository.login(email: email, password: password),
    );
  }

  Future<bool> register({
    required String name,
    required String email,
    required String password,
    required String passwordConfirmation,
    String? phone,
    String? whatsapp,
  }) async {
    return _authenticate(
      () => _repository.register(
        name: name,
        email: email,
        password: password,
        passwordConfirmation: passwordConfirmation,
        phone: phone,
        whatsapp: whatsapp,
      ),
    );
  }

  Future<bool> loginWithGoogle() async {
    return _authenticate(() async {
      await _ensureGoogleInitialized();
      final account = await GoogleSignIn.instance.authenticate();
      final idToken = account.authentication.idToken;
      if (idToken == null || idToken.isEmpty) {
        throw Exception('Missing Google ID token');
      }
      return _repository.loginWithGoogleIdToken(idToken);
    });
  }

  Future<void> loadAccountTypes() async {
    if (accountTypes.isNotEmpty) return;

    try {
      accountTypes.assignAll(await _repository.accountTypes());
    } catch (_) {
      accountTypes.assignAll(_fallbackAccountTypes);
    }
  }

  Future<bool> updateAccountType(String accountType) async {
    isLoading.value = true;
    error.value = null;

    try {
      user.value = await _repository.updateAccountType(accountType);
      return true;
    } on DioException catch (exception) {
      error.value = _messageFromDio(exception);
      return false;
    } catch (_) {
      error.value = 'تعذر حفظ نوع الحساب. حاول مرة أخرى.';
      return false;
    } finally {
      isLoading.value = false;
    }
  }

  Future<bool> _authenticate(Future<AuthSession> Function() action) async {
    isLoading.value = true;
    error.value = null;

    try {
      final session = await action();
      token.value = session.token;
      user.value = session.user;
      _apiClient.setAuthToken(session.token);

      try {
        await _secureStorage.write(key: _tokenKey, value: session.token);
        final prefs = await SharedPreferences.getInstance();
        await prefs.setString(_tokenKey, session.token);
      } catch (_) {
        // The session can still work for the current app run if browser storage
        // is blocked or temporarily unavailable.
      }

      return true;
    } on DioException catch (exception) {
      error.value = _messageFromDio(exception);
      return false;
    } catch (_) {
      error.value = 'تعذر إتمام العملية. حاول مرة أخرى.';
      return false;
    } finally {
      isLoading.value = false;
    }
  }

  Future<void> logout() async {
    isLoading.value = true;

    try {
      if (_apiClient.hasToken) await _repository.logout();
      if (_googleInitialized) await GoogleSignIn.instance.signOut();
    } catch (_) {
      // Local logout must still complete even if the token is already expired.
    } finally {
      await clearSession();
      isLoading.value = false;
    }
  }

  Future<void> clearSession() async {
    try {
      await _secureStorage.delete(key: _tokenKey);
      final prefs = await SharedPreferences.getInstance();
      await prefs.remove(_tokenKey);
    } catch (_) {
      // Clearing in-memory state is enough when persistent storage is blocked.
    }
    token.value = null;
    user.value = null;
    _apiClient.setAuthToken(null);
  }

  String _messageFromDio(DioException exception) {
    final data = _normalizeErrorData(exception.response?.data);

    if (data is Map<String, dynamic>) {
      final message = data['message'];
      if (message is String && message.isNotEmpty) return message;

      final errors = data['errors'];
      if (errors is Map && errors.isNotEmpty) {
        final first = errors.values.first;
        if (first is List && first.isNotEmpty) return '${first.first}';
      }
    }

    if (data is String && data.contains('<html')) {
      return 'استجابة الخادم غير صحيحة. تأكد أن رابط API يعمل.';
    }

    return 'تعذر الاتصال بالخادم.';
  }

  dynamic _normalizeErrorData(dynamic data) {
    if (data is String && data.trim().isNotEmpty) {
      try {
        return jsonDecode(data);
      } catch (_) {
        return data;
      }
    }

    if (data is Map<String, dynamic>) return data;
    if (data is Map) return Map<String, dynamic>.from(data);
    return data;
  }

  Future<void> _ensureGoogleInitialized() async {
    if (_googleInitialized) return;

    await GoogleSignIn.instance.initialize(
      clientId: AppConfig.googleClientId.isEmpty
          ? null
          : AppConfig.googleClientId,
      serverClientId: AppConfig.googleServerClientId.isEmpty
          ? null
          : AppConfig.googleServerClientId,
    );
    _googleInitialized = true;
  }
}

const _fallbackAccountTypes = <AccountTypeOption>[
  AccountTypeOption(value: 'regular_user', labelAr: 'مستخدم عادي'),
  AccountTypeOption(value: 'chalet_owner', labelAr: 'صاحب شاليه'),
  AccountTypeOption(value: 'sports_field_owner', labelAr: 'صاحب ملعب'),
  AccountTypeOption(value: 'wedding_hall_owner', labelAr: 'صاحب صالة أفراح'),
  AccountTypeOption(value: 'hotel_owner', labelAr: 'صاحب فندق'),
  AccountTypeOption(value: 'tourism_office_owner', labelAr: 'صاحب مكتب سياحي'),
  AccountTypeOption(value: 'transport_company_owner', labelAr: 'صاحب شركة نقل'),
  AccountTypeOption(
    value: 'commercial_property_owner',
    labelAr: 'صاحب محل أو مكتب تجاري',
  ),
  AccountTypeOption(value: 'service_provider', labelAr: 'صاحب خدمة'),
  AccountTypeOption(value: 'technician', labelAr: 'فني / عامل صيانة'),
  AccountTypeOption(value: 'nursery_owner', labelAr: 'صاحب مشتل'),
  AccountTypeOption(value: 'turkish_bath_owner', labelAr: 'صاحب حمام تركي'),
  AccountTypeOption(value: 'amusement_city_owner', labelAr: 'صاحب مدينة ألعاب'),
  AccountTypeOption(value: 'travel_agency_owner', labelAr: 'صاحب مكتب سفريات'),
  AccountTypeOption(value: 'airline_company_owner', labelAr: 'صاحب شركة طيران'),
  AccountTypeOption(
    value: 'parcel_service_owner',
    labelAr: 'صاحب خدمة إرسال طرود',
  ),
];
