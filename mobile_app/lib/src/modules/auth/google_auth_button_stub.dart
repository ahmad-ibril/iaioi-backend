import 'package:flutter/material.dart';
import 'package:get/get.dart';

import 'auth_controller.dart';

class GoogleAuthButton extends StatelessWidget {
  const GoogleAuthButton({
    required this.isSignUp,
    required this.onSuccess,
    super.key,
  });

  final bool isSignUp;
  final VoidCallback onSuccess;

  @override
  Widget build(BuildContext context) {
    final auth = Get.find<UserAuthController>();

    return Obx(
      () => SizedBox(
        width: double.infinity,
        child: OutlinedButton(
          onPressed: auth.isLoading.value ? null : () => _authenticate(auth),
          style: OutlinedButton.styleFrom(
            backgroundColor: Colors.white,
            foregroundColor: const Color(0xFF3C4043),
            side: const BorderSide(color: Color(0xFFDADCE0)),
          ),
          child: Text(
            isSignUp
                ? 'الاشتراك باستخدام Google'
                : 'تسجيل الدخول باستخدام Google',
          ),
        ),
      ),
    );
  }

  Future<void> _authenticate(UserAuthController auth) async {
    if (await auth.loginWithGoogle()) onSuccess();
  }
}
