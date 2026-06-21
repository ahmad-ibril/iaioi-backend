import 'package:flutter/material.dart';

import '../../data/models/category_model.dart';
import 'category_card.dart';

class CategoryTile extends StatelessWidget {
  const CategoryTile({
    super.key,
    required this.category,
    required this.onTap,
    this.compact = false,
  });

  final CategoryModel category;
  final VoidCallback onTap;
  final bool compact;

  @override
  Widget build(BuildContext context) {
    return CategoryCard(category: category, onTap: onTap, horizontal: !compact);
  }
}
