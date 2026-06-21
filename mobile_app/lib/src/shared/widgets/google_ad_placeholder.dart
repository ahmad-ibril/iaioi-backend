import 'dart:async';

import 'package:flutter/material.dart';

import '../../core/config/app_config.dart';
import '../../core/config/app_theme.dart';

enum GoogleAdPlaceholderType { banner, native, inline, details }

class GoogleAdPlaceholder extends StatelessWidget {
  const GoogleAdPlaceholder({
    super.key,
    this.type = GoogleAdPlaceholderType.banner,
    this.margin = const EdgeInsets.symmetric(vertical: 12),
    this.label,
  });

  final GoogleAdPlaceholderType type;
  final EdgeInsetsGeometry margin;
  final String? label;

  @override
  Widget build(BuildContext context) {
    final isNative =
        type == GoogleAdPlaceholderType.native ||
        type == GoogleAdPlaceholderType.inline;
    final height = switch (type) {
      GoogleAdPlaceholderType.banner => 68.0,
      GoogleAdPlaceholderType.native => 132.0,
      GoogleAdPlaceholderType.inline => 116.0,
      GoogleAdPlaceholderType.details => 76.0,
    };

    return Padding(
      padding: margin,
      child: DecoratedBox(
        decoration: BoxDecoration(
          color: AppTheme.secondarySoft,
          border: Border.all(color: AppTheme.secondary.withValues(alpha: 0.22)),
          borderRadius: BorderRadius.circular(8),
        ),
        child: SizedBox(
          width: double.infinity,
          height: height,
          child: Padding(
            padding: const EdgeInsets.symmetric(horizontal: 14),
            child: Row(
              children: [
                DecoratedBox(
                  decoration: BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: SizedBox(
                    width: isNative ? 52 : 42,
                    height: isNative ? 52 : 42,
                    child: const Icon(
                      Icons.ads_click_rounded,
                      color: AppTheme.secondary,
                    ),
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        label ?? _titleFor(type),
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: Theme.of(context).textTheme.titleSmall,
                      ),
                      const SizedBox(height: 2),
                      Text(
                        AppConfig.adMobEnabled
                            ? AppConfig.webAdPlaceholder
                            : 'مكان مؤقت جاهز للربط مع google_mobile_ads لاحقاً',
                        maxLines: 2,
                        overflow: TextOverflow.ellipsis,
                        style: Theme.of(context).textTheme.bodySmall,
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  String _titleFor(GoogleAdPlaceholderType type) {
    return switch (type) {
      GoogleAdPlaceholderType.banner => 'مساحة إعلان Google Banner',
      GoogleAdPlaceholderType.native => 'مساحة إعلان Google Native',
      GoogleAdPlaceholderType.inline => 'إعلان Google بين النتائج',
      GoogleAdPlaceholderType.details => 'إعلان Google أسفل التفاصيل',
    };
  }
}

class AppOpenAdPlaceholder extends StatefulWidget {
  const AppOpenAdPlaceholder({super.key, this.enabled = true});

  final bool enabled;

  @override
  State<AppOpenAdPlaceholder> createState() => _AppOpenAdPlaceholderState();
}

class _AppOpenAdPlaceholderState extends State<AppOpenAdPlaceholder> {
  static bool _shownThisSession = false;

  @override
  void initState() {
    super.initState();
    if (!widget.enabled || _shownThisSession) return;
    _shownThisSession = true;
    WidgetsBinding.instance.addPostFrameCallback((_) => _show());
  }

  Future<void> _show() async {
    if (!mounted) return;
    await showDialog<void>(
      context: context,
      barrierDismissible: false,
      builder: (_) => const _AppOpenAdDialog(),
    );
  }

  @override
  Widget build(BuildContext context) => const SizedBox.shrink();
}

class _AppOpenAdDialog extends StatefulWidget {
  const _AppOpenAdDialog();

  @override
  State<_AppOpenAdDialog> createState() => _AppOpenAdDialogState();
}

class _AppOpenAdDialogState extends State<_AppOpenAdDialog> {
  Timer? _timer;
  bool canSkip = false;

  @override
  void initState() {
    super.initState();
    _timer = Timer(const Duration(seconds: 2), () {
      if (mounted) setState(() => canSkip = true);
    });
  }

  @override
  void dispose() {
    _timer?.cancel();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Dialog.fullscreen(
      backgroundColor: AppTheme.textDark,
      child: SafeArea(
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            children: [
              Align(
                alignment: AlignmentDirectional.centerEnd,
                child: TextButton(
                  onPressed: canSkip ? () => Navigator.of(context).pop() : null,
                  child: Text(canSkip ? 'تخطي' : 'إعلان مؤقت'),
                ),
              ),
              const Spacer(),
              const Icon(
                Icons.ads_click_rounded,
                size: 72,
                color: Colors.white,
              ),
              const SizedBox(height: 18),
              Text(
                'App Open Ad Placeholder',
                textAlign: TextAlign.center,
                style: Theme.of(
                  context,
                ).textTheme.headlineSmall?.copyWith(color: Colors.white),
              ),
              const SizedBox(height: 8),
              Text(
                'هذه مساحة مؤقتة فقط ويمكن ربطها لاحقاً بإعلان فتح التطبيق.',
                textAlign: TextAlign.center,
                style: Theme.of(
                  context,
                ).textTheme.bodyMedium?.copyWith(color: Colors.white70),
              ),
              const Spacer(),
            ],
          ),
        ),
      ),
    );
  }
}

class InterstitialAdPlaceholder extends StatelessWidget {
  const InterstitialAdPlaceholder({super.key, required this.onClose});

  final VoidCallback onClose;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppTheme.textDark,
      body: SafeArea(
        child: Padding(
          padding: const EdgeInsets.all(18),
          child: Column(
            children: [
              Align(
                alignment: AlignmentDirectional.centerEnd,
                child: IconButton.filledTonal(
                  onPressed: onClose,
                  icon: const Icon(Icons.close_rounded),
                  tooltip: 'إغلاق',
                ),
              ),
              const Spacer(),
              const Icon(
                Icons.ads_click_rounded,
                color: Colors.white,
                size: 72,
              ),
              const SizedBox(height: 16),
              Text(
                'Interstitial Ad Placeholder',
                style: Theme.of(
                  context,
                ).textTheme.headlineSmall?.copyWith(color: Colors.white),
              ),
              const SizedBox(height: 8),
              Text(
                'مساحة جاهزة لاحقاً للإعلانات البينية.',
                textAlign: TextAlign.center,
                style: Theme.of(
                  context,
                ).textTheme.bodyMedium?.copyWith(color: Colors.white70),
              ),
              const Spacer(),
            ],
          ),
        ),
      ),
    );
  }
}
