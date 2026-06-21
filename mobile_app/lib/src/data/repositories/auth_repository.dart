import '../../core/network/api_client.dart';
import '../models/user_model.dart';

class AuthSession {
  const AuthSession({required this.token, required this.user});

  final String token;
  final UserModel user;

  factory AuthSession.fromJson(Map<String, dynamic> json) {
    return AuthSession(
      token: json['token'] ?? '',
      user: UserModel.fromJson(Map<String, dynamic>.from(json['user'] ?? {})),
    );
  }
}

class AccountTypeOption {
  const AccountTypeOption({
    required this.value,
    required this.labelAr,
    this.requiresVerification = false,
  });

  final String value;
  final String labelAr;
  final bool requiresVerification;

  factory AccountTypeOption.fromJson(Map<String, dynamic> json) {
    return AccountTypeOption(
      value: json['value'] ?? '',
      labelAr: json['label_ar'] ?? json['value'] ?? '',
      requiresVerification: json['requires_verification'] ?? false,
    );
  }
}

class AuthRepository {
  AuthRepository(this._apiClient);

  final ApiClient _apiClient;

  Future<AuthSession> login({
    required String email,
    required String password,
  }) async {
    final data = await _apiClient.postMap(
      '/auth/login',
      data: {'email': email, 'password': password},
    );

    return AuthSession.fromJson(data);
  }

  Future<AuthSession> register({
    required String name,
    required String email,
    required String password,
    required String passwordConfirmation,
    String? phone,
    String? whatsapp,
  }) async {
    final data = await _apiClient.postMap(
      '/auth/register',
      data: {
        'name': name,
        'email': email,
        'password': password,
        'password_confirmation': passwordConfirmation,
        if (phone != null && phone.isNotEmpty) 'phone': phone,
        if (whatsapp != null && whatsapp.isNotEmpty) 'whatsapp': whatsapp,
      },
    );

    return AuthSession.fromJson(data);
  }

  Future<AuthSession> loginWithGoogleIdToken(String idToken) async {
    final data = await _apiClient.postMap(
      '/auth/google',
      data: {'id_token': idToken},
    );

    return AuthSession.fromJson(data);
  }

  Future<UserModel> me() async {
    final data = await _apiClient.getMap('/auth/me');
    return UserModel.fromJson(Map<String, dynamic>.from(data['user'] ?? {}));
  }

  Future<UserModel> updateProfile({
    required String name,
    String? phone,
    String? whatsapp,
  }) async {
    final data = await _apiClient.putMap(
      '/auth/profile',
      data: {'name': name, 'phone': phone, 'whatsapp': whatsapp},
    );

    return UserModel.fromJson(Map<String, dynamic>.from(data['user'] ?? {}));
  }

  Future<List<AccountTypeOption>> accountTypes() async {
    final data = await _apiClient.getMap('/account-types');
    return (data['data'] as List? ?? [])
        .whereType<Map<String, dynamic>>()
        .map(AccountTypeOption.fromJson)
        .where((option) => option.value.isNotEmpty)
        .toList();
  }

  Future<UserModel> updateAccountType(String accountType) async {
    final data = await _apiClient.patchMap(
      '/auth/account-type',
      data: {'account_type': accountType},
    );

    return UserModel.fromJson(Map<String, dynamic>.from(data['user'] ?? {}));
  }

  Future<void> logout() async {
    await _apiClient.postMap('/auth/logout');
  }
}
