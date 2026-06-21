import 'package:flutter/material.dart';

import '../../core/config/app_theme.dart';
import '../../core/utils/category_presentation.dart';
import '../../data/models/category_model.dart';

class CategoryCard extends StatelessWidget {
  const CategoryCard({
    super.key,
    required this.category,
    required this.onTap,
    this.horizontal = false,
  });

  final CategoryModel category;
  final VoidCallback onTap;
  final bool horizontal;

  @override
  Widget build(BuildContext context) {
    final count = category.listingsCount ?? 0;
    final accent = _accentFor(category);

    return Material(
      color: Colors.white,
      borderRadius: BorderRadius.circular(8),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(8),
        child: DecoratedBox(
          decoration: BoxDecoration(
            border: Border.all(color: AppTheme.border),
            borderRadius: BorderRadius.circular(8),
            boxShadow: [
              BoxShadow(
                color: Colors.black.withValues(alpha: 0.045),
                blurRadius: 18,
                offset: const Offset(0, 10),
              ),
            ],
          ),
          child: Padding(
            padding: const EdgeInsets.all(12),
            child: horizontal
                ? _row(context, count, accent)
                : _column(context, count, accent),
          ),
        ),
      ),
    );
  }

  Widget _row(BuildContext context, int count, Color accent) {
    return Row(
      children: [
        _icon(accent),
        const SizedBox(width: 12),
        Expanded(child: _texts(context, count, center: false)),
        const Icon(Icons.chevron_left_rounded, color: AppTheme.textMuted),
      ],
    );
  }

  Widget _column(BuildContext context, int count, Color accent) {
    return Column(
      mainAxisAlignment: MainAxisAlignment.center,
      children: [
        _icon(accent),
        const SizedBox(height: 10),
        _texts(context, count, center: true),
      ],
    );
  }

  Widget _texts(BuildContext context, int count, {required bool center}) {
    return Column(
      crossAxisAlignment: center
          ? CrossAxisAlignment.center
          : CrossAxisAlignment.start,
      mainAxisSize: MainAxisSize.min,
      children: [
        Text(
          category.nameAr,
          textAlign: center ? TextAlign.center : TextAlign.start,
          maxLines: 2,
          overflow: TextOverflow.ellipsis,
          style: Theme.of(context).textTheme.titleSmall,
        ),
        const SizedBox(height: 4),
        Text(
          count > 0
              ? '$count إعلان'
              : CategoryPresentation.subtitleFor(category),
          textAlign: center ? TextAlign.center : TextAlign.start,
          maxLines: 1,
          overflow: TextOverflow.ellipsis,
          style: Theme.of(context).textTheme.bodySmall,
        ),
      ],
    );
  }

  Widget _icon(Color accent) {
    return DecoratedBox(
      decoration: BoxDecoration(
        color: accent.withValues(alpha: 0.12),
        borderRadius: BorderRadius.circular(8),
      ),
      child: SizedBox(
        width: 48,
        height: 48,
        child: Icon(
          CategoryPresentation.iconFor(category),
          color: accent,
          size: 27,
        ),
      ),
    );
  }

  Color _accentFor(CategoryModel category) {
    return switch (category.groupKey) {
      'bookings' => AppTheme.primary,
      'entertainment-tourism' => AppTheme.secondary,
      'real-estate' => const Color(0xFF2563EB),
      'services' => const Color(0xFF7C3AED),
      'garden-nursery' => AppTheme.green,
      _ => AppTheme.textDark,
    };
  }
}
