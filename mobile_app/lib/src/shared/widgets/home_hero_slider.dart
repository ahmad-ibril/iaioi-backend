import 'dart:async';

import 'package:flutter/material.dart';

import '../../core/config/app_theme.dart';

class HomeHeroSlider extends StatefulWidget {
  const HomeHeroSlider({super.key, required this.onActionTap});

  final VoidCallback onActionTap;

  @override
  State<HomeHeroSlider> createState() => _HomeHeroSliderState();
}

class _HomeHeroSliderState extends State<HomeHeroSlider> {
  final _controller = PageController();
  final _slides = const [
    _HeroSlide(
      title: 'احجز مكانك القادم بثقة',
      subtitle: 'شاليهات، صالات، فنادق وخدمات تأجير في واجهة واحدة سهلة.',
      action: 'استعرض الإعلانات',
      imageUrl:
          'https://images.unsplash.com/photo-1505693416388-ac5ce068fe85?auto=format&fit=crop&w=1200&q=80',
    ),
    _HeroSlide(
      title: 'عروض مميزة لهذا الأسبوع',
      subtitle: 'اكتشف خيارات جاهزة للحجز والتواصل السريع مع صاحب الإعلان.',
      action: 'شاهد العروض',
      imageUrl:
          'https://images.unsplash.com/photo-1566073771259-6a8506099945?auto=format&fit=crop&w=1200&q=80',
    ),
    _HeroSlide(
      title: 'كل ما تحتاجه للمناسبات',
      subtitle: 'صالات، تجهيزات، سيارات وخدمات مساندة بتجربة بحث بسيطة.',
      action: 'ابدأ البحث',
      imageUrl:
          'https://images.unsplash.com/photo-1519167758481-83f550bb49b3?auto=format&fit=crop&w=1200&q=80',
    ),
  ];
  Timer? _timer;
  int _page = 0;

  @override
  void initState() {
    super.initState();
    _timer = Timer.periodic(const Duration(seconds: 5), (_) => _next());
  }

  @override
  void dispose() {
    _timer?.cancel();
    _controller.dispose();
    super.dispose();
  }

  void _next() {
    if (!_controller.hasClients || _slides.isEmpty) return;
    final next = (_page + 1) % _slides.length;
    _controller.animateToPage(
      next,
      duration: const Duration(milliseconds: 450),
      curve: Curves.easeOutCubic,
    );
  }

  @override
  Widget build(BuildContext context) {
    final height = MediaQuery.sizeOf(context).width >= 700 ? 270.0 : 238.0;

    return Column(
      children: [
        ClipRRect(
          borderRadius: BorderRadius.circular(8),
          child: SizedBox(
            height: height,
            child: PageView.builder(
              controller: _controller,
              itemCount: _slides.length,
              onPageChanged: (value) => setState(() => _page = value),
              itemBuilder: (context, index) {
                return _HeroSlideCard(
                  slide: _slides[index],
                  onActionTap: widget.onActionTap,
                );
              },
            ),
          ),
        ),
        const SizedBox(height: 8),
        Row(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            for (var index = 0; index < _slides.length; index++)
              AnimatedContainer(
                duration: const Duration(milliseconds: 220),
                margin: const EdgeInsets.symmetric(horizontal: 3),
                width: _page == index ? 22 : 7,
                height: 7,
                decoration: BoxDecoration(
                  color: _page == index ? AppTheme.primary : AppTheme.border,
                  borderRadius: BorderRadius.circular(8),
                ),
              ),
          ],
        ),
      ],
    );
  }
}

class _HeroSlideCard extends StatelessWidget {
  const _HeroSlideCard({required this.slide, required this.onActionTap});

  final _HeroSlide slide;
  final VoidCallback onActionTap;

  @override
  Widget build(BuildContext context) {
    return Stack(
      fit: StackFit.expand,
      children: [
        Image.network(
          slide.imageUrl,
          fit: BoxFit.cover,
          errorBuilder: (context, error, stackTrace) {
            return Container(
              color: AppTheme.secondarySoft,
              child: const Icon(
                Icons.villa_outlined,
                color: AppTheme.secondary,
                size: 72,
              ),
            );
          },
        ),
        DecoratedBox(
          decoration: BoxDecoration(
            gradient: LinearGradient(
              begin: AlignmentDirectional.centerEnd,
              end: AlignmentDirectional.centerStart,
              colors: [
                Colors.black.withValues(alpha: 0.68),
                Colors.black.withValues(alpha: 0.2),
                Colors.transparent,
              ],
            ),
          ),
        ),
        Padding(
          padding: const EdgeInsets.all(18),
          child: Align(
            alignment: AlignmentDirectional.centerStart,
            child: ConstrainedBox(
              constraints: const BoxConstraints(maxWidth: 430),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    slide.title,
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                    style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                      color: Colors.white,
                      height: 1.22,
                    ),
                  ),
                  const SizedBox(height: 8),
                  Text(
                    slide.subtitle,
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                    style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                      color: Colors.white.withValues(alpha: 0.92),
                    ),
                  ),
                  const SizedBox(height: 14),
                  FilledButton.icon(
                    onPressed: onActionTap,
                    icon: const Icon(Icons.search_rounded),
                    label: Text(slide.action),
                  ),
                ],
              ),
            ),
          ),
        ),
      ],
    );
  }
}

class _HeroSlide {
  const _HeroSlide({
    required this.title,
    required this.subtitle,
    required this.action,
    required this.imageUrl,
  });

  final String title;
  final String subtitle;
  final String action;
  final String imageUrl;
}
