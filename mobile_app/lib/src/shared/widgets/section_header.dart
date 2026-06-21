import 'package:flutter/material.dart';

import '../../core/config/app_theme.dart';

class SectionHeader extends StatelessWidget {
  const SectionHeader({
    super.key,
    required this.title,
    this.subtitle,
    this.action,
    this.onActionTap,
  });

  final String title;
  final String? subtitle;
  final String? action;
  final VoidCallback? onActionTap;

  @override
  Widget build(BuildContext context) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.center,
      children: [
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(title, style: Theme.of(context).textTheme.titleLarge),
              if ((subtitle ?? '').isNotEmpty) ...[
                const SizedBox(height: 2),
                Text(
                  subtitle!,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: Theme.of(context).textTheme.bodySmall,
                ),
              ],
            ],
          ),
        ),
        if (action != null && onActionTap != null)
          TextButton.icon(
            onPressed: onActionTap,
            icon: const Icon(Icons.arrow_back_rounded, size: 18),
            label: Text(action!, maxLines: 1, overflow: TextOverflow.ellipsis),
            style: TextButton.styleFrom(foregroundColor: AppTheme.primaryDark),
          ),
      ],
    );
  }
}
