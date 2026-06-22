import 'dart:async';

import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:google_sign_in/google_sign_in.dart';
import 'package:google_sign_in_web/web_only.dart' as web;

import 'auth_controller.dart';

class GoogleAuthButton extends StatefulWidget {
  const GoogleAuthButton({
    required this.isSignUp,
    required this.onSuccess,
    super.key,
  });

  final bool isSignUp;
  final VoidCallback onSuccess;

  @override
  State<GoogleAuthButton> createState() => _GoogleAuthButtonState();
}

class _GoogleAuthButtonState extends State<GoogleAuthButton> {
  StreamSubscription<GoogleSignInAuthenticationEvent>? _subscription;
  Widget? _button;
  String? _setupError;

  @override
  void initState() {
    super.initState();
    _initialize();
  }

  @override
  void dispose() {
    _subscription?.cancel();
    super.dispose();
  }

  Future<void> _initialize() async {
    final auth = Get.find<UserAuthController>();

    _subscription = GoogleSignIn.instance.authenticationEvents.listen(
      _handleAuthenticationEvent,
      onError: (_) =>
          _setSetupError('تعذر إتمام تسجيل الدخول باستخدام Google.'),
    );

    try {
      await auth.initializeGoogle();
      if (!mounted) return;

      setState(() {
        _button = web.renderButton(
          configuration: web.GSIButtonConfiguration(
            type: web.GSIButtonType.standard,
            theme: web.GSIButtonTheme.outline,
            size: web.GSIButtonSize.large,
            text: widget.isSignUp
                ? web.GSIButtonText.signupWith
                : web.GSIButtonText.signinWith,
            shape: web.GSIButtonShape.rectangular,
            logoAlignment: web.GSIButtonLogoAlignment.left,
            minimumWidth: 280,
            locale: 'ar',
          ),
        );
      });
    } catch (_) {
      _setSetupError('يجب ضبط Google Web Client ID لتفعيل تسجيل الدخول.');
    }
  }

  Future<void> _handleAuthenticationEvent(
    GoogleSignInAuthenticationEvent event,
  ) async {
    if (event is! GoogleSignInAuthenticationEventSignIn) return;

    final ok = await Get.find<UserAuthController>().loginWithGoogleAccount(
      event.user,
    );
    if (ok && mounted) widget.onSuccess();
  }

  void _setSetupError(String message) {
    if (!mounted) return;
    setState(() => _setupError = message);
  }

  @override
  Widget build(BuildContext context) {
    if (_setupError != null) {
      return Text(
        _setupError!,
        textAlign: TextAlign.center,
        style: TextStyle(color: Theme.of(context).colorScheme.error),
      );
    }

    if (_button == null) {
      return const SizedBox(
        height: 44,
        child: Center(child: CircularProgressIndicator(strokeWidth: 2)),
      );
    }

    final auth = Get.find<UserAuthController>();
    return Obx(
      () => AbsorbPointer(
        absorbing: auth.isLoading.value,
        child: Opacity(
          opacity: auth.isLoading.value ? 0.6 : 1,
          child: Center(child: _button!),
        ),
      ),
    );
  }
}
