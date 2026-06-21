import 'package:get/get.dart';

import '../core/network/api_client.dart';
import '../core/services/launch_service.dart';
import '../core/services/location_service.dart';
import '../data/repositories/auth_repository.dart';
import '../data/repositories/admin_repository.dart';
import '../data/repositories/booking_repository.dart';
import '../data/repositories/catalog_repository.dart';
import '../data/repositories/favorites_repository.dart';
import '../data/repositories/listing_management_repository.dart';
import '../modules/account/account_page.dart';
import '../modules/ads/sponsored_ads_screen.dart';
import '../modules/admin/admin_dashboard_page.dart';
import '../modules/auth/account_type_page.dart';
import '../modules/auth/auth_controller.dart';
import '../modules/auth/login_page.dart';
import '../modules/auth/register_page.dart';
import '../modules/bookings/booking_controller.dart';
import '../modules/bookings/booking_request_page.dart';
import '../modules/bookings/my_booking_requests_page.dart';
import '../modules/categories/categories_page.dart';
import '../modules/details/listing_details_page.dart';
import '../modules/favorites/favorites_controller.dart';
import '../modules/favorites/favorites_page.dart';
import '../modules/filters/filters_page.dart';
import '../modules/home/home_controller.dart';
import '../modules/home/home_page.dart';
import '../modules/listings/all_listings_page.dart';
import '../modules/listings/listings_page.dart';
import '../modules/my_listings/add_listing_page.dart';
import '../modules/my_listings/my_listings_controller.dart';
import '../modules/my_listings/my_listings_page.dart';
import '../modules/owner/owner_dashboard_page.dart';
import '../modules/splash/splash_page.dart';
import 'app_routes.dart';

class AppBindings extends Bindings {
  @override
  void dependencies() {
    Get.put(ApiClient(), permanent: true);
    Get.put(LocationService(), permanent: true);
    Get.put(LaunchService(), permanent: true);
    Get.put(CatalogRepository(Get.find<ApiClient>()), permanent: true);
    Get.put(AuthRepository(Get.find<ApiClient>()), permanent: true);
    Get.put(AdminRepository(Get.find<ApiClient>()), permanent: true);
    Get.put(FavoritesRepository(Get.find<ApiClient>()), permanent: true);
    Get.put(BookingRepository(Get.find<ApiClient>()), permanent: true);
    Get.put(
      ListingManagementRepository(Get.find<ApiClient>()),
      permanent: true,
    );
    Get.put(
      UserAuthController(Get.find<AuthRepository>(), Get.find<ApiClient>()),
      permanent: true,
    );
    Get.put(
      FavoritesController(
        Get.find<FavoritesRepository>(),
        Get.find<UserAuthController>(),
      ),
      permanent: true,
    );
    Get.put(
      BookingController(
        Get.find<BookingRepository>(),
        Get.find<UserAuthController>(),
      ),
      permanent: true,
    );
    Get.put(
      MyListingsController(
        Get.find<ListingManagementRepository>(),
        Get.find<UserAuthController>(),
      ),
      permanent: true,
    );
    Get.put(
      HomeController(
        Get.find<CatalogRepository>(),
        Get.find<LocationService>(),
      ),
      permanent: true,
    );
  }
}

class AppPages {
  const AppPages._();

  static final pages = [
    GetPage(name: AppRoutes.splash, page: () => const SplashPage()),
    GetPage(name: AppRoutes.home, page: () => const HomePage()),
    GetPage(name: AppRoutes.categories, page: () => const CategoriesPage()),
    GetPage(name: AppRoutes.allListings, page: () => const AllListingsPage()),
    GetPage(
      name: AppRoutes.sponsoredAds,
      page: () => const SponsoredAdsScreen(),
    ),
    GetPage(name: AppRoutes.listings, page: () => const ListingsPage()),
    GetPage(name: AppRoutes.details, page: () => const ListingDetailsPage()),
    GetPage(name: AppRoutes.filters, page: () => const FiltersPage()),
    GetPage(name: AppRoutes.favorites, page: () => const FavoritesPage()),
    GetPage(
      name: AppRoutes.bookingRequest,
      page: () => const BookingRequestPage(),
    ),
    GetPage(
      name: AppRoutes.myRequests,
      page: () => const MyBookingRequestsPage(),
    ),
    GetPage(name: AppRoutes.addListing, page: () => const AddListingPage()),
    GetPage(name: AppRoutes.myListings, page: () => const MyListingsPage()),
    GetPage(
      name: AppRoutes.ownerDashboard,
      page: () => const OwnerDashboardPage(),
    ),
    GetPage(
      name: AppRoutes.adminDashboard,
      page: () => const AdminDashboardPage(),
    ),
    GetPage(name: AppRoutes.login, page: () => const LoginPage()),
    GetPage(name: AppRoutes.register, page: () => const RegisterPage()),
    GetPage(name: AppRoutes.accountType, page: () => const AccountTypePage()),
    GetPage(name: AppRoutes.account, page: () => const AccountPage()),
  ];
}
