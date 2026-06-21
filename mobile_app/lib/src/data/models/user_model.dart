class UserModel {
  const UserModel({
    required this.id,
    required this.name,
    this.email,
    this.phone,
    this.whatsapp,
    this.accountType,
    this.accountTypeLabel,
    this.requiresVerification = false,
    this.verificationStatus,
    this.role,
  });

  final int id;
  final String name;
  final String? email;
  final String? phone;
  final String? whatsapp;
  final String? accountType;
  final String? accountTypeLabel;
  final bool requiresVerification;
  final String? verificationStatus;
  final String? role;

  factory UserModel.fromJson(Map<String, dynamic> json) {
    return UserModel(
      id: json['id'] ?? 0,
      name: json['name'] ?? '',
      email: json['email'],
      phone: json['phone'],
      whatsapp: json['whatsapp'],
      accountType: json['account_type'],
      accountTypeLabel: json['account_type_label'],
      requiresVerification: json['requires_verification'] ?? false,
      verificationStatus: json['verification_status'],
      role: json['role'],
    );
  }
}
