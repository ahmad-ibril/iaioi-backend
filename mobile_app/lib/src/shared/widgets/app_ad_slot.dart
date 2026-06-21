import 'package:flutter/material.dart';

import 'google_ad_placeholder.dart';

enum AppAdPlacement { banner, native }

class AppAdSlot extends StatelessWidget {
  const AppAdSlot({
    super.key,
    required this.placement,
    this.margin = const EdgeInsets.symmetric(vertical: 10),
  });

  final AppAdPlacement placement;
  final EdgeInsetsGeometry margin;

  @override
  Widget build(BuildContext context) {
    return GoogleAdPlaceholder(
      type: placement == AppAdPlacement.native
          ? GoogleAdPlaceholderType.native
          : GoogleAdPlaceholderType.banner,
      margin: margin,
    );
  }
}
