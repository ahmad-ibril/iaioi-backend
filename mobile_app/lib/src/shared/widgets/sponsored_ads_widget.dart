import 'dart:async';

import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../core/config/app_theme.dart';
import '../../core/services/launch_service.dart';
import '../../routes/app_routes.dart';

enum SponsoredAdPlacement {
  homeTop,
  betweenCategories,
  betweenListings,
  listingDetails,
  fullScreen,
}

enum SponsoredAdMediaType { image, video }

class SponsoredAdModel {
  const SponsoredAdModel({
    required this.id,
    required this.title,
    required this.description,
    required this.imageUrl,
    required this.cta,
    required this.placement,
    this.mediaType = SponsoredAdMediaType.image,
    this.videoUrl,
    this.link,
    this.external = false,
  });

  final int id;
  final String title;
  final String description;
  final String imageUrl;
  final String cta;
  final SponsoredAdPlacement placement;
  final SponsoredAdMediaType mediaType;
  final String? videoUrl;
  final String? link;
  final bool external;
}

class SponsoredAdsWidget extends StatelessWidget {
  const SponsoredAdsWidget({
    super.key,
    required this.placement,
    this.margin = const EdgeInsets.symmetric(vertical: 12),
    this.compact = false,
  });

  final SponsoredAdPlacement placement;
  final EdgeInsetsGeometry margin;
  final bool compact;

  @override
  Widget build(BuildContext context) {
    final ads = SponsoredAdsMockData.forPlacement(placement);
    if (ads.isEmpty) return const SizedBox.shrink();

    final ad = ads.first;
    return Padding(
      padding: margin,
      child: _SponsoredAdCard(ad: ad, compact: compact),
    );
  }
}

class SponsoredAdsStrip extends StatelessWidget {
  const SponsoredAdsStrip({
    super.key,
    required this.placement,
    this.height = 172,
  });

  final SponsoredAdPlacement placement;
  final double height;

  @override
  Widget build(BuildContext context) {
    final ads = SponsoredAdsMockData.forPlacement(placement);
    if (ads.isEmpty) return const SizedBox.shrink();

    return SizedBox(
      height: height,
      child: ListView.separated(
        scrollDirection: Axis.horizontal,
        itemCount: ads.length,
        separatorBuilder: (context, index) => const SizedBox(width: 10),
        itemBuilder: (context, index) => SizedBox(
          width: 300,
          child: _SponsoredAdCard(ad: ads[index], compact: true),
        ),
      ),
    );
  }
}

class SponsoredFullScreenAd extends StatefulWidget {
  const SponsoredFullScreenAd({super.key, this.ad});

  final SponsoredAdModel? ad;

  @override
  State<SponsoredFullScreenAd> createState() => _SponsoredFullScreenAdState();
}

class _SponsoredFullScreenAdState extends State<SponsoredFullScreenAd> {
  Timer? _timer;
  bool canSkip = false;

  SponsoredAdModel get ad =>
      widget.ad ??
      SponsoredAdsMockData.forPlacement(SponsoredAdPlacement.fullScreen).first;

  @override
  void initState() {
    super.initState();
    final delay = ad.mediaType == SponsoredAdMediaType.video
        ? const Duration(seconds: 5)
        : const Duration(seconds: 4);
    _timer = Timer(delay, () {
      if (!mounted) return;
      if (ad.mediaType == SponsoredAdMediaType.image) {
        Navigator.of(context).maybePop();
      } else {
        setState(() => canSkip = true);
      }
    });
  }

  @override
  void dispose() {
    _timer?.cancel();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final isVideo = ad.mediaType == SponsoredAdMediaType.video;

    return Scaffold(
      backgroundColor: AppTheme.textDark,
      body: SafeArea(
        child: Stack(
          children: [
            Positioned.fill(
              child: Image.network(
                ad.imageUrl,
                fit: BoxFit.cover,
                errorBuilder: (context, error, stackTrace) =>
                    const ColoredBox(color: AppTheme.textDark),
              ),
            ),
            Positioned.fill(
              child: DecoratedBox(
                decoration: BoxDecoration(
                  gradient: LinearGradient(
                    begin: Alignment.topCenter,
                    end: Alignment.bottomCenter,
                    colors: [
                      Colors.black.withValues(alpha: 0.2),
                      Colors.black.withValues(alpha: 0.8),
                    ],
                  ),
                ),
              ),
            ),
            Padding(
              padding: const EdgeInsets.all(18),
              child: Column(
                children: [
                  Align(
                    alignment: AlignmentDirectional.centerEnd,
                    child: TextButton(
                      onPressed: !isVideo || canSkip
                          ? () => Navigator.of(context).maybePop()
                          : null,
                      child: Text(
                        isVideo && !canSkip ? 'تخطي بعد 5 ثوان' : 'تخطي',
                      ),
                    ),
                  ),
                  const Spacer(),
                  if (isVideo)
                    const Icon(
                      Icons.play_circle_fill_rounded,
                      color: Colors.white,
                      size: 74,
                    ),
                  const SizedBox(height: 14),
                  Text(
                    ad.title,
                    textAlign: TextAlign.center,
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                    style: Theme.of(
                      context,
                    ).textTheme.headlineSmall?.copyWith(color: Colors.white),
                  ),
                  const SizedBox(height: 8),
                  Text(
                    ad.description,
                    textAlign: TextAlign.center,
                    maxLines: 3,
                    overflow: TextOverflow.ellipsis,
                    style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                      color: Colors.white.withValues(alpha: 0.9),
                    ),
                  ),
                  const SizedBox(height: 16),
                  FilledButton.icon(
                    onPressed: () => _openAd(ad),
                    icon: const Icon(Icons.open_in_new_rounded),
                    label: Text(ad.cta),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _SponsoredAdCard extends StatelessWidget {
  const _SponsoredAdCard({required this.ad, required this.compact});

  final SponsoredAdModel ad;
  final bool compact;

  @override
  Widget build(BuildContext context) {
    return Material(
      color: Colors.white,
      borderRadius: BorderRadius.circular(8),
      clipBehavior: Clip.antiAlias,
      child: InkWell(
        onTap: () => _openAd(ad),
        child: DecoratedBox(
          decoration: BoxDecoration(
            border: Border.all(color: AppTheme.border),
            borderRadius: BorderRadius.circular(8),
            boxShadow: [
              BoxShadow(
                color: Colors.black.withValues(alpha: 0.05),
                blurRadius: 18,
                offset: const Offset(0, 10),
              ),
            ],
          ),
          child: compact ? _compact(context) : _wide(context),
        ),
      ),
    );
  }

  Widget _wide(BuildContext context) {
    return SizedBox(
      height: 160,
      child: Row(
        children: [
          _media(width: 126, height: 160),
          Expanded(child: _content(context)),
        ],
      ),
    );
  }

  Widget _compact(BuildContext context) {
    return SizedBox(
      height: 172,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          _media(width: double.infinity, height: 92),
          Expanded(child: _content(context, tight: true)),
        ],
      ),
    );
  }

  Widget _content(BuildContext context, {bool tight = false}) {
    return Padding(
      padding: EdgeInsets.all(tight ? 10 : 12),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        mainAxisSize: tight ? MainAxisSize.min : MainAxisSize.max,
        children: [
          const _SponsoredLabel(),
          const SizedBox(height: 7),
          Text(
            ad.title,
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
            style: Theme.of(context).textTheme.titleMedium,
          ),
          const SizedBox(height: 4),
          Text(
            ad.description,
            maxLines: tight ? 1 : 2,
            overflow: TextOverflow.ellipsis,
            style: Theme.of(context).textTheme.bodySmall,
          ),
          if (tight) ...[
            const SizedBox(height: 2),
            Text(
              ad.cta,
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: Theme.of(context).textTheme.bodySmall?.copyWith(
                color: AppTheme.primaryDark,
                fontWeight: FontWeight.w900,
              ),
            ),
          ] else ...[
            const Spacer(),
            const SizedBox(height: 8),
            Align(
              alignment: AlignmentDirectional.centerStart,
              child: FilledButton.tonalIcon(
                onPressed: () => _openAd(ad),
                icon: const Icon(Icons.open_in_new_rounded, size: 18),
                label: Text(ad.cta),
              ),
            ),
          ],
        ],
      ),
    );
  }

  Widget _media({required double width, required double height}) {
    return SizedBox(
      width: width,
      height: height,
      child: Stack(
        fit: StackFit.expand,
        children: [
          Image.network(
            ad.imageUrl,
            fit: BoxFit.cover,
            errorBuilder: (context, error, stackTrace) => Container(
              color: AppTheme.surfaceWarm,
              child: const Icon(
                Icons.campaign_outlined,
                color: AppTheme.primary,
                size: 38,
              ),
            ),
          ),
          if (ad.mediaType == SponsoredAdMediaType.video)
            const Center(
              child: Icon(
                Icons.play_circle_fill_rounded,
                color: Colors.white,
                size: 42,
              ),
            ),
        ],
      ),
    );
  }
}

class _SponsoredLabel extends StatelessWidget {
  const _SponsoredLabel();

  @override
  Widget build(BuildContext context) {
    return DecoratedBox(
      decoration: BoxDecoration(
        color: AppTheme.warning.withValues(alpha: 0.14),
        borderRadius: BorderRadius.circular(8),
      ),
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
        child: Text(
          'إعلان ممول',
          style: Theme.of(context).textTheme.bodySmall?.copyWith(
            color: const Color(0xFF9A5B00),
            fontWeight: FontWeight.w900,
          ),
        ),
      ),
    );
  }
}

class SponsoredAdsMockData {
  const SponsoredAdsMockData._();

  static const ads = [
    SponsoredAdModel(
      id: 1,
      title: 'خصم على شاليهات نهاية الأسبوع',
      description: 'احصل على عروض موسمية عند الحجز المبكر.',
      imageUrl:
          'https://images.unsplash.com/photo-1564013799919-ab600027ffc6?auto=format&fit=crop&w=900&q=80',
      cta: 'شاهد العرض',
      placement: SponsoredAdPlacement.homeTop,
      link: AppRoutes.allListings,
    ),
    SponsoredAdModel(
      id: 2,
      title: 'تجهيز مناسبتك من مكان واحد',
      description: 'صالات، تصوير، ديكور وسيارات للزفاف.',
      imageUrl:
          'https://images.unsplash.com/photo-1519225421980-715cb0215aed?auto=format&fit=crop&w=900&q=80',
      cta: 'ابدأ الآن',
      placement: SponsoredAdPlacement.betweenCategories,
      mediaType: SponsoredAdMediaType.video,
      link: AppRoutes.allListings,
    ),
    SponsoredAdModel(
      id: 3,
      title: 'عرض ممول من مزود خدمة',
      description: 'إعلان تجريبي بين النتائج قابل للربط بلوحة التحكم.',
      imageUrl:
          'https://images.unsplash.com/photo-1542314831-068cd1dbfeeb?auto=format&fit=crop&w=900&q=80',
      cta: 'تفاصيل أكثر',
      placement: SponsoredAdPlacement.betweenListings,
      link: 'https://example.com',
      external: true,
    ),
    SponsoredAdModel(
      id: 4,
      title: 'مقترحات قريبة من هذا الإعلان',
      description: 'مساحة ممولة داخل صفحة التفاصيل.',
      imageUrl:
          'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?auto=format&fit=crop&w=900&q=80',
      cta: 'استكشف',
      placement: SponsoredAdPlacement.listingDetails,
      link: AppRoutes.allListings,
    ),
    SponsoredAdModel(
      id: 5,
      title: 'إعلان شاشة كاملة',
      description: 'Mock full screen ad يظهر مؤقتاً مع إمكانية التخطي للفيديو.',
      imageUrl:
          'https://images.unsplash.com/photo-1499793983690-e29da59ef1c2?auto=format&fit=crop&w=1200&q=80',
      cta: 'فتح الإعلان',
      placement: SponsoredAdPlacement.fullScreen,
      mediaType: SponsoredAdMediaType.video,
      link: AppRoutes.allListings,
    ),
  ];

  static List<SponsoredAdModel> forPlacement(SponsoredAdPlacement placement) {
    return ads.where((ad) => ad.placement == placement).toList();
  }
}

void _openAd(SponsoredAdModel ad) {
  final link = ad.link;
  if (link == null || link.isEmpty) return;

  if (ad.external) {
    if (Get.isRegistered<LaunchService>()) {
      Get.find<LaunchService>().openUrl(link);
    }
    return;
  }

  Get.toNamed(link);
}
