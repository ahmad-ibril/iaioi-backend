import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../routes/app_routes.dart';
import 'auth_controller.dart';

class RegisterPage extends StatefulWidget {
  const RegisterPage({super.key});

  @override
  State<RegisterPage> createState() => _RegisterPageState();
}

class _RegisterPageState extends State<RegisterPage> {
  final formKey = GlobalKey<FormState>();
  final nameController = TextEditingController();
  final emailController = TextEditingController();
  final phoneController = TextEditingController();
  final whatsappController = TextEditingController();
  final passwordController = TextEditingController();
  final confirmController = TextEditingController();

  @override
  void dispose() {
    nameController.dispose();
    emailController.dispose();
    phoneController.dispose();
    whatsappController.dispose();
    passwordController.dispose();
    confirmController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final auth = Get.find<UserAuthController>();

    return Scaffold(
      appBar: AppBar(title: const Text('إنشاء حساب')),
      body: Center(
        child: ConstrainedBox(
          constraints: const BoxConstraints(maxWidth: 520),
          child: Form(
            key: formKey,
            child: ListView(
              padding: const EdgeInsets.all(16),
              children: [
                TextFormField(
                  controller: nameController,
                  decoration: const InputDecoration(
                    labelText: 'الاسم',
                    prefixIcon: Icon(Icons.person_outline),
                  ),
                  validator: _required,
                ),
                const SizedBox(height: 12),
                TextFormField(
                  controller: emailController,
                  keyboardType: TextInputType.emailAddress,
                  decoration: const InputDecoration(
                    labelText: 'البريد الإلكتروني',
                    prefixIcon: Icon(Icons.email_outlined),
                  ),
                  validator: _required,
                ),
                const SizedBox(height: 12),
                TextFormField(
                  controller: phoneController,
                  keyboardType: TextInputType.phone,
                  decoration: const InputDecoration(
                    labelText: 'رقم الهاتف اختياري',
                    prefixIcon: Icon(Icons.phone_outlined),
                  ),
                ),
                const SizedBox(height: 12),
                TextFormField(
                  controller: whatsappController,
                  keyboardType: TextInputType.phone,
                  decoration: const InputDecoration(
                    labelText: 'رقم واتساب اختياري',
                    prefixIcon: Icon(Icons.chat_outlined),
                  ),
                ),
                const SizedBox(height: 12),
                TextFormField(
                  controller: passwordController,
                  obscureText: true,
                  decoration: const InputDecoration(
                    labelText: 'كلمة المرور',
                    prefixIcon: Icon(Icons.lock_outline),
                  ),
                  validator: (value) {
                    if (value == null || value.isEmpty) {
                      return 'أدخل كلمة المرور';
                    }
                    if (value.length < 8) {
                      return 'كلمة المرور يجب أن تكون 8 أحرف على الأقل';
                    }
                    return null;
                  },
                ),
                const SizedBox(height: 12),
                TextFormField(
                  controller: confirmController,
                  obscureText: true,
                  decoration: const InputDecoration(
                    labelText: 'تأكيد كلمة المرور',
                    prefixIcon: Icon(Icons.lock_reset_outlined),
                  ),
                  validator: (value) => value != passwordController.text
                      ? 'تأكيد كلمة المرور غير مطابق'
                      : null,
                ),
                const SizedBox(height: 18),
                Obx(
                  () => FilledButton.icon(
                    onPressed: auth.isLoading.value ? null : _submit,
                    icon: const Icon(Icons.person_add_alt_1),
                    label: const Text('إنشاء الحساب'),
                  ),
                ),
                Obx(
                  () => auth.error.value == null
                      ? const SizedBox.shrink()
                      : Padding(
                          padding: const EdgeInsets.only(top: 12),
                          child: Text(
                            auth.error.value!,
                            style: const TextStyle(color: Colors.red),
                          ),
                        ),
                ),
                const SizedBox(height: 10),
                Obx(
                  () => OutlinedButton.icon(
                    onPressed: auth.isLoading.value ? null : _googleRegister,
                    icon: const Icon(Icons.g_mobiledata),
                    label: const Text('التسجيل بجوجل'),
                  ),
                ),
                const SizedBox(height: 10),
                TextButton(
                  onPressed: () => Get.offNamed(AppRoutes.login),
                  child: const Text('لدي حساب بالفعل'),
                ),
                TextButton.icon(
                  onPressed: () async {
                    await Get.find<UserAuthController>().clearSession();
                    Get.offAllNamed(AppRoutes.home);
                  },
                  icon: const Icon(Icons.visibility_outlined),
                  label: const Text('الدخول كزائر'),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  String? _required(String? value) {
    return value == null || value.trim().isEmpty ? 'هذا الحقل مطلوب' : null;
  }

  Future<void> _submit() async {
    if (!formKey.currentState!.validate()) return;

    final ok = await Get.find<UserAuthController>().register(
      name: nameController.text.trim(),
      email: emailController.text.trim(),
      phone: phoneController.text.trim(),
      whatsapp: whatsappController.text.trim(),
      password: passwordController.text,
      passwordConfirmation: confirmController.text,
    );

    if (ok) Get.offNamed(AppRoutes.accountType);
  }

  Future<void> _googleRegister() async {
    final ok = await Get.find<UserAuthController>().loginWithGoogle();
    if (ok) Get.offNamed(AppRoutes.accountType);
  }
}
