import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:get/get.dart';

import 'package:arab_rentals_app/src/core/config/app_theme.dart';
import 'package:arab_rentals_app/src/core/network/api_client.dart';
import 'package:arab_rentals_app/src/data/models/category_model.dart';
import 'package:arab_rentals_app/src/data/models/listing_model.dart';
import 'package:arab_rentals_app/src/data/repositories/auth_repository.dart';
import 'package:arab_rentals_app/src/data/repositories/favorites_repository.dart';
import 'package:arab_rentals_app/src/modules/auth/auth_controller.dart';
import 'package:arab_rentals_app/src/modules/favorites/favorites_controller.dart';
import 'package:arab_rentals_app/src/shared/widgets/empty_state.dart';
import 'package:arab_rentals_app/src/shared/widgets/listing_card.dart';
import 'package:arab_rentals_app/src/shared/widgets/sponsored_ads_widget.dart';

void main() {
  tearDown(Get.reset);

  testWidgets('Empty state smoke test', (WidgetTester tester) async {
    await tester.pumpWidget(
      const MaterialApp(
        home: Directionality(
          textDirection: TextDirection.rtl,
          child: Scaffold(body: EmptyState(message: 'لا توجد بيانات')),
        ),
      ),
    );

    expect(find.byIcon(Icons.info_outline), findsOneWidget);
  });

  testWidgets('Compact listing card fits the home strip height', (
    WidgetTester tester,
  ) async {
    Get.put<FavoritesController>(_TestFavoritesController());

    await tester.pumpWidget(
      _TestApp(
        child: Center(
          child: SizedBox(
            width: 285,
            height: 154,
            child: ListingCard(
              listing: const ListingModel(
                id: 1,
                titleAr: 'Family chalet with pool',
                slug: 'family-chalet-with-pool',
                category: CategoryModel(
                  id: 1,
                  nameAr: 'Chalets',
                  slug: 'chalets',
                ),
                cityName: 'Amman',
                basePrice: '100.00',
                currencyCode: 'JOD',
                priceUnit: 'day',
              ),
              compact: true,
            ),
          ),
        ),
      ),
    );

    await tester.pump();

    expect(tester.takeException(), isNull);
  });

  testWidgets('Sponsored ad card fits the home content width', (
    WidgetTester tester,
  ) async {
    await tester.pumpWidget(
      const _TestApp(
        child: SizedBox(
          width: 928,
          child: SponsoredAdsWidget(
            placement: SponsoredAdPlacement.betweenCategories,
            margin: EdgeInsets.zero,
          ),
        ),
      ),
    );

    await tester.pump();

    expect(tester.takeException(), isNull);
  });
}

class _TestApp extends StatelessWidget {
  const _TestApp({required this.child});

  final Widget child;

  @override
  Widget build(BuildContext context) {
    return GetMaterialApp(
      theme: AppTheme.light,
      home: Directionality(
        textDirection: TextDirection.rtl,
        child: Scaffold(body: child),
      ),
    );
  }
}

class _TestFavoritesController extends FavoritesController {
  _TestFavoritesController()
    : super(
        FavoritesRepository(ApiClient()),
        UserAuthController(AuthRepository(ApiClient()), ApiClient()),
      );

  @override
  Future<void> toggle(ListingModel listing) async {}
}
