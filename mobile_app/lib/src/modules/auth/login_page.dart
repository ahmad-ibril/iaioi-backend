import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../routes/app_routes.dart';
import 'auth_controller.dart';
import 'google_auth_button.dart';

class LoginPage extends StatefulWidget {
  const LoginPage({super.key});

  @override
  State<LoginPage> createState() => _LoginPageState();
}

class _LoginPageState extends State<LoginPage> {
  final emailController = TextEditingController();
  final passwordController = TextEditingController();
  final formKey = GlobalKey<FormState>();

  @override
  void dispose() {
    emailController.dispose();
    passwordController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final auth = Get.find<UserAuthController>();

    return Scaffold(
      appBar: AppBar(title: const Text('تسجيل الدخول')),
      body: Center(
        child: ConstrainedBox(
          constraints: const BoxConstraints(maxWidth: 460),
          child: ListView(
            padding: const EdgeInsets.all(16),
            shrinkWrap: true,
            children: [
              Text(
                'مرحبا بك',
                style: Theme.of(context).textTheme.headlineSmall,
              ),
              const SizedBox(height: 8),
              Text(
                'سجل الدخول لحفظ المفضلة وإدارة إعلاناتك وطلباتك.',
                style: Theme.of(context).textTheme.bodyMedium,
              ),
              const SizedBox(height: 20),
              Form(
                key: formKey,
                child: Column(
                  children: [
                    TextFormField(
                      controller: emailController,
                      keyboardType: TextInputType.emailAddress,
                      decoration: const InputDecoration(
                        labelText: 'البريد الإلكتروني',
                        prefixIcon: Icon(Icons.email_outlined),
                      ),
                      validator: (value) =>
                          (value == null || value.trim().isEmpty)
                          ? 'أدخل البريد الإلكتروني'
                          : null,
                    ),
                    const SizedBox(height: 12),
                    TextFormField(
                      controller: passwordController,
                      obscureText: true,
                      decoration: const InputDecoration(
                        labelText: 'كلمة المرور',
                        prefixIcon: Icon(Icons.lock_outline),
                      ),
                      validator: (value) => (value == null || value.isEmpty)
                          ? 'أدخل كلمة المرور'
                          : null,
                    ),
                    const SizedBox(height: 18),
                    Obx(
                      () => SizedBox(
                        width: double.infinity,
                        child: FilledButton.icon(
                          onPressed: auth.isLoading.value ? null : _submit,
                          icon: auth.isLoading.value
                              ? const SizedBox(
                                  width: 18,
                                  height: 18,
                                  child: CircularProgressIndicator(
                                    strokeWidth: 2,
                                  ),
                                )
                              : const Icon(Icons.login),
                          label: const Text('دخول'),
                        ),
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
                    GoogleAuthButton(
                      isSignUp: false,
                      onSuccess: () => Get.offNamed(AppRoutes.home),
                    ),
                    const SizedBox(height: 10),
                    TextButton(
                      onPressed: () => Get.toNamed(AppRoutes.register),
                      child: const Text('إنشاء حساب جديد'),
                    ),
                    TextButton.icon(
                      onPressed: _continueAsGuest,
                      icon: const Icon(Icons.visibility_outlined),
                      label: const Text('الدخول كزائر'),
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Future<void> _submit() async {
    if (!formKey.currentState!.validate()) return;

    final ok = await Get.find<UserAuthController>().login(
      email: emailController.text.trim(),
      password: passwordController.text,
    );

    if (ok) Get.offNamed(AppRoutes.home);
  }

  Future<void> _continueAsGuest() async {
    await Get.find<UserAuthController>().clearSession();
    Get.offAllNamed(AppRoutes.home);
  }
}
