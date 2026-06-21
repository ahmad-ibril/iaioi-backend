import 'package:flutter/material.dart';

import '../../core/config/app_theme.dart';

class AppSearchBar extends StatelessWidget {
  const AppSearchBar({
    super.key,
    this.controller,
    this.hint = 'ابحث عن إعلان أو خدمة...',
    this.readOnly = false,
    this.onTap,
    this.onSubmitted,
    this.onFilterTap,
  });

  final TextEditingController? controller;
  final String hint;
  final bool readOnly;
  final VoidCallback? onTap;
  final ValueChanged<String>? onSubmitted;
  final VoidCallback? onFilterTap;

  @override
  Widget build(BuildContext context) {
    return DecoratedBox(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(8),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.07),
            blurRadius: 22,
            offset: const Offset(0, 10),
          ),
        ],
      ),
      child: TextField(
        controller: controller,
        readOnly: readOnly,
        onTap: onTap,
        onSubmitted: onSubmitted,
        textInputAction: TextInputAction.search,
        decoration: InputDecoration(
          hintText: hint,
          prefixIcon: const Icon(Icons.search_rounded, color: AppTheme.primary),
          suffixIcon: onFilterTap == null
              ? null
              : Padding(
                  padding: const EdgeInsetsDirectional.only(end: 6),
                  child: IconButton.filled(
                    onPressed: onFilterTap,
                    icon: const Icon(Icons.tune_rounded, size: 20),
                    tooltip: 'الفلاتر',
                    style: IconButton.styleFrom(
                      backgroundColor: AppTheme.textDark,
                      foregroundColor: Colors.white,
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(8),
                      ),
                    ),
                  ),
                ),
          filled: true,
          fillColor: Colors.white,
          hintStyle: Theme.of(
            context,
          ).textTheme.bodyMedium?.copyWith(color: AppTheme.textMuted),
          enabledBorder: OutlineInputBorder(
            borderRadius: BorderRadius.circular(8),
            borderSide: const BorderSide(color: AppTheme.border),
          ),
          focusedBorder: OutlineInputBorder(
            borderRadius: BorderRadius.circular(8),
            borderSide: const BorderSide(color: AppTheme.primary, width: 1.3),
          ),
        ),
      ),
    );
  }
}
