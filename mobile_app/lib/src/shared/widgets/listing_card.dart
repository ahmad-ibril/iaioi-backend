import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../core/config/app_theme.dart';
import '../../core/utils/category_presentation.dart';
import '../../data/models/listing_model.dart';
import '../../modules/favorites/favorites_controller.dart';
import '../../routes/app_routes.dart';

enum ListingCardStyle { booking, service, product }

class ListingCard extends StatelessWidget {
  const ListingCard({
    super.key,
    required this.listing,
    this.style,
    this.compact = false,
  });

  final ListingModel listing;
  final ListingCardStyle? style;
  final bool compact;

  @override
  Widget build(BuildContext context) {
    if (compact) return _CompactListingCard(listing: listing);

    final resolvedStyle = style ?? _styleForListing();

    return switch (resolvedStyle) {
      ListingCardStyle.service => _HorizontalListingCard(
        listing: listing,
        imageSize: 106,
        fallbackIcon: Icons.handyman_outlined,
      ),
      ListingCardStyle.product => _ProductCard(listing: listing),
      ListingCardStyle.booking => _HorizontalListingCard(listing: listing),
    };
  }

  ListingCardStyle _styleForListing() {
    if (CategoryPresentation.usesProductLayout(listing.category)) {
      return ListingCardStyle.product;
    }
    if (CategoryPresentation.usesSimpleServiceLayout(listing.category)) {
      return ListingCardStyle.service;
    }

    return ListingCardStyle.booking;
  }
}

class _HorizontalListingCard extends StatelessWidget {
  const _HorizontalListingCard({
    required this.listing,
    this.imageSize = 132,
    this.fallbackIcon = Icons.image_outlined,
  });

  final ListingModel listing;
  final double imageSize;
  final IconData fallbackIcon;

  @override
  Widget build(BuildContext context) {
    final favorites = Get.find<FavoritesController>();

    return _CardShell(
      child: InkWell(
        onTap: () => _openDetails(listing),
        borderRadius: BorderRadius.circular(8),
        child: Padding(
          padding: const EdgeInsets.all(10),
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              _ImageThumb(
                imageUrl: listing.coverImageUrl,
                width: imageSize,
                height: imageSize,
                icon: fallbackIcon,
                favoriteButton: Obx(
                  () => _FavoriteButton(
                    selected: favorites.isFavorite(listing),
                    onPressed: () => favorites.toggle(listing),
                  ),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(child: _ListingContent(listing: listing)),
            ],
          ),
        ),
      ),
    );
  }
}

class _CompactListingCard extends StatelessWidget {
  const _CompactListingCard({required this.listing});

  final ListingModel listing;

  @override
  Widget build(BuildContext context) {
    final favorites = Get.find<FavoritesController>();

    return _CardShell(
      margin: EdgeInsets.zero,
      child: InkWell(
        onTap: () => _openDetails(listing),
        borderRadius: BorderRadius.circular(8),
        child: Padding(
          padding: const EdgeInsets.all(10),
          child: Row(
            children: [
              _ImageThumb(
                imageUrl: listing.coverImageUrl,
                width: 94,
                height: 94,
                favoriteButton: Obx(
                  () => _FavoriteButton(
                    selected: favorites.isFavorite(listing),
                    onPressed: () => favorites.toggle(listing),
                    small: true,
                  ),
                ),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: _ListingContent(
                  listing: listing,
                  compact: true,
                  showRating: false,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _ProductCard extends StatelessWidget {
  const _ProductCard({required this.listing});

  final ListingModel listing;

  @override
  Widget build(BuildContext context) {
    final favorites = Get.find<FavoritesController>();

    return _CardShell(
      margin: EdgeInsets.zero,
      child: InkWell(
        onTap: () => _openDetails(listing),
        borderRadius: BorderRadius.circular(8),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            _ImageThumb(
              imageUrl: listing.coverImageUrl,
              width: double.infinity,
              height: 118,
              icon: Icons.local_florist_outlined,
              favoriteButton: Obx(
                () => _FavoriteButton(
                  selected: favorites.isFavorite(listing),
                  onPressed: () => favorites.toggle(listing),
                ),
              ),
            ),
            Expanded(
              child: Padding(
                padding: const EdgeInsets.all(10),
                child: _ListingContent(
                  listing: listing,
                  compact: true,
                  showLocation: false,
                  showRating: false,
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _ListingContent extends StatelessWidget {
  const _ListingContent({
    required this.listing,
    this.compact = false,
    this.showLocation = true,
    this.showRating = true,
  });

  final ListingModel listing;
  final bool compact;
  final bool showLocation;
  final bool showRating;

  @override
  Widget build(BuildContext context) {
    final badges = _badgesFor(listing, compact: compact);

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      mainAxisSize: MainAxisSize.min,
      children: [
        if (badges.isNotEmpty) ...[
          Wrap(spacing: 6, runSpacing: 6, children: badges),
          const SizedBox(height: 8),
        ],
        Text(
          listing.titleAr,
          maxLines: compact ? 1 : 2,
          overflow: TextOverflow.ellipsis,
          style: Theme.of(context).textTheme.titleMedium?.copyWith(
            height: 1.25,
            fontWeight: FontWeight.w800,
          ),
        ),
        if (showLocation && listing.locationText.isNotEmpty) ...[
          const SizedBox(height: 6),
          _IconText(icon: Icons.place_outlined, text: listing.locationText),
        ],
        const SizedBox(height: 8),
        Row(
          crossAxisAlignment: CrossAxisAlignment.end,
          children: [
            Expanded(child: _PriceText(listing: listing)),
            if (showRating && !compact) const _RatingPill(),
          ],
        ),
      ],
    );
  }

  List<Widget> _badgesFor(ListingModel listing, {required bool compact}) {
    final badges = <Widget>[
      _Badge(
        text: listing.category?.nameAr ?? 'إعلان',
        color: AppTheme.secondary,
        soft: true,
      ),
    ];

    if (listing.isFeatured) {
      badges.add(const _Badge(text: 'مميز', color: AppTheme.primary));
    }
    if (listing.hasSpecialOffer) {
      badges.add(const _Badge(text: 'عرض خاص', color: AppTheme.warning));
    }
    if (!compact && _isAvailableToday(listing)) {
      badges.add(const _Badge(text: 'متاح اليوم', color: AppTheme.green));
    }

    return badges;
  }
}

class _PriceText extends StatelessWidget {
  const _PriceText({required this.listing});

  final ListingModel listing;

  @override
  Widget build(BuildContext context) {
    final unit = _priceUnitLabel(listing.priceUnit);
    final price = listing.basePrice == null || listing.basePrice!.isEmpty
        ? 'السعر عند التواصل'
        : '${listing.basePrice} ${listing.currencyCode ?? ''}'.trim();

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      mainAxisSize: MainAxisSize.min,
      children: [
        Text(
          price,
          maxLines: 1,
          overflow: TextOverflow.ellipsis,
          style: Theme.of(context).textTheme.titleSmall?.copyWith(
            color: AppTheme.textDark,
            fontWeight: FontWeight.w900,
          ),
        ),
        if (unit.isNotEmpty)
          Text(
            unit,
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
            style: Theme.of(context).textTheme.bodySmall,
          ),
      ],
    );
  }
}

class _CardShell extends StatelessWidget {
  const _CardShell({required this.child, this.margin});

  final Widget child;
  final EdgeInsetsGeometry? margin;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: margin ?? const EdgeInsets.only(bottom: 12),
      child: Material(
        color: Colors.white,
        borderRadius: BorderRadius.circular(8),
        clipBehavior: Clip.antiAlias,
        child: DecoratedBox(
          decoration: BoxDecoration(
            border: Border.all(color: AppTheme.border),
            borderRadius: BorderRadius.circular(8),
            boxShadow: [
              BoxShadow(
                color: Colors.black.withValues(alpha: 0.055),
                blurRadius: 18,
                offset: const Offset(0, 10),
              ),
            ],
          ),
          child: child,
        ),
      ),
    );
  }
}

class _ImageThumb extends StatelessWidget {
  const _ImageThumb({
    required this.imageUrl,
    required this.width,
    required this.height,
    this.favoriteButton,
    this.icon = Icons.image_outlined,
  });

  final String? imageUrl;
  final double width;
  final double height;
  final Widget? favoriteButton;
  final IconData icon;

  @override
  Widget build(BuildContext context) {
    return ClipRRect(
      borderRadius: BorderRadius.circular(8),
      child: SizedBox(
        width: width,
        height: height,
        child: Stack(
          fit: StackFit.expand,
          children: [
            if (imageUrl == null || imageUrl!.isEmpty)
              Container(
                color: AppTheme.secondarySoft,
                child: Icon(icon, size: 34, color: AppTheme.secondary),
              )
            else
              CachedNetworkImage(
                imageUrl: imageUrl!,
                fit: BoxFit.cover,
                placeholder: (context, url) => Container(
                  color: AppTheme.secondarySoft,
                  child: const Center(
                    child: CircularProgressIndicator(strokeWidth: 2),
                  ),
                ),
                errorWidget: (context, url, error) => Container(
                  color: AppTheme.secondarySoft,
                  child: const Icon(
                    Icons.image_not_supported_outlined,
                    color: AppTheme.textMuted,
                  ),
                ),
              ),
            Positioned.fill(
              child: DecoratedBox(
                decoration: BoxDecoration(
                  gradient: LinearGradient(
                    begin: Alignment.topCenter,
                    end: Alignment.bottomCenter,
                    colors: [
                      Colors.black.withValues(alpha: 0.16),
                      Colors.transparent,
                    ],
                  ),
                ),
              ),
            ),
            if (favoriteButton != null)
              PositionedDirectional(top: 7, start: 7, child: favoriteButton!),
          ],
        ),
      ),
    );
  }
}

class _FavoriteButton extends StatelessWidget {
  const _FavoriteButton({
    required this.selected,
    required this.onPressed,
    this.small = false,
  });

  final bool selected;
  final VoidCallback onPressed;
  final bool small;

  @override
  Widget build(BuildContext context) {
    final size = small ? 30.0 : 36.0;
    return Material(
      color: Colors.white.withValues(alpha: 0.94),
      shape: const CircleBorder(),
      child: IconButton(
        onPressed: onPressed,
        icon: Icon(
          selected ? Icons.favorite_rounded : Icons.favorite_border_rounded,
          color: selected ? AppTheme.primary : AppTheme.textDark,
        ),
        iconSize: small ? 18 : 20,
        constraints: BoxConstraints.tightFor(width: size, height: size),
        padding: EdgeInsets.zero,
        tooltip: 'المفضلة',
      ),
    );
  }
}

class _Badge extends StatelessWidget {
  const _Badge({required this.text, required this.color, this.soft = false});

  final String text;
  final Color color;
  final bool soft;

  @override
  Widget build(BuildContext context) {
    return DecoratedBox(
      decoration: BoxDecoration(
        color: soft ? color.withValues(alpha: 0.12) : color,
        borderRadius: BorderRadius.circular(8),
      ),
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
        child: Text(
          text,
          maxLines: 1,
          overflow: TextOverflow.ellipsis,
          style: Theme.of(context).textTheme.bodySmall?.copyWith(
            color: soft ? color : Colors.white,
            fontWeight: FontWeight.w900,
            height: 1.15,
          ),
        ),
      ),
    );
  }
}

class _IconText extends StatelessWidget {
  const _IconText({required this.icon, required this.text});

  final IconData icon;
  final String text;

  @override
  Widget build(BuildContext context) {
    return Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        Icon(icon, size: 15, color: AppTheme.textMuted),
        const SizedBox(width: 4),
        Flexible(
          child: Text(
            text,
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
            style: Theme.of(context).textTheme.bodySmall,
          ),
        ),
      ],
    );
  }
}

class _RatingPill extends StatelessWidget {
  const _RatingPill();

  @override
  Widget build(BuildContext context) {
    return DecoratedBox(
      decoration: BoxDecoration(
        color: const Color(0xFFFFF7E6),
        borderRadius: BorderRadius.circular(8),
      ),
      child: const Padding(
        padding: EdgeInsets.symmetric(horizontal: 7, vertical: 4),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(Icons.star_rounded, size: 16, color: Color(0xFFF5B301)),
            SizedBox(width: 2),
            Text('4.8'),
          ],
        ),
      ),
    );
  }
}

bool _isAvailableToday(ListingModel listing) {
  if (listing.availableToday) return true;

  final today = DateUtils.dateOnly(DateTime.now());
  return listing.availabilitySlots.any(
    (slot) => slot.isAvailable && DateUtils.isSameDay(slot.date, today),
  );
}

String _priceUnitLabel(String? unit) {
  return switch (unit) {
    'hour' => 'لكل ساعة',
    'day' => 'لكل يوم',
    'night' => 'لكل ليلة',
    'trip' => 'لكل رحلة',
    'person' => 'لكل شخص',
    'month' => 'لكل شهر',
    'product' => 'للمنتج',
    _ => '',
  };
}

void _openDetails(ListingModel listing) {
  Get.toNamed(
    AppRoutes.details,
    arguments: {'slug': listing.slug, 'listing': listing},
  );
}
