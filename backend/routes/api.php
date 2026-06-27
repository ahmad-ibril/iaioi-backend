<?php

use App\Http\Controllers\Api\V1\Admin\AdminAvailabilitySlotController;
use App\Http\Controllers\Api\V1\Admin\AdminBannerController;
use App\Http\Controllers\Api\V1\Admin\AdminBookingRequestController;
use App\Http\Controllers\Api\V1\Admin\AdminCategoryController;
use App\Http\Controllers\Api\V1\Admin\AdminCategoryFilterController;
use App\Http\Controllers\Api\V1\Admin\AdminDashboardController;
use App\Http\Controllers\Api\V1\Admin\AdminListingController;
use App\Http\Controllers\Api\V1\Admin\AdminLocationController;
use App\Http\Controllers\Api\V1\Admin\AdminSettingController;
use App\Http\Controllers\Api\V1\Admin\AdminUserController;
use App\Http\Controllers\Api\V1\Admin\AdminVerificationRequestController;
use App\Http\Controllers\Api\V1\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\AvailabilitySlotController;
use App\Http\Controllers\Api\V1\BookingRequestController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\DiagnosticsController;
use App\Http\Controllers\Api\V1\FavoriteController;
use App\Http\Controllers\Api\V1\FilterOptionController;
use App\Http\Controllers\Api\V1\HomeController;
use App\Http\Controllers\Api\V1\ListingController;
use App\Http\Controllers\Api\V1\LocationController;
use App\Http\Controllers\Api\V1\UserListingController;
use App\Http\Controllers\Api\V1\VerificationRequestController;
use App\Http\Controllers\Api\V1\WantedRequestController;
use Illuminate\Support\Facades\Route;

Route::get('/debug', [DiagnosticsController::class, 'debug']);

Route::prefix('v1')->group(function (): void {
    Route::get('/health', [DiagnosticsController::class, 'health']);
    Route::get('/home', HomeController::class);

    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/categories/{category:slug}', [CategoryController::class, 'show']);
    Route::get('/categories/{category:slug}/listings', [ListingController::class, 'byCategory']);

    Route::get('/listings', [ListingController::class, 'index']);
    Route::get('/listings/{listing:slug}', [ListingController::class, 'show']);
    Route::get('/listings/{listing}/availability', [AvailabilitySlotController::class, 'index'])->whereNumber('listing');
    Route::get('/listings/{listing:slug}/availability', [AvailabilitySlotController::class, 'index']);

    Route::get('/wanted-requests', [WantedRequestController::class, 'index']);
    Route::get('/wanted-requests/{wantedRequest}', [WantedRequestController::class, 'show']);

    Route::get('/filters/options', FilterOptionController::class);
    Route::get('/locations/countries', [LocationController::class, 'countries']);
    Route::get('/locations/cities', [LocationController::class, 'cities']);

    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::post('/auth/google', [AuthController::class, 'google']);
    Route::get('/account-types', [AuthController::class, 'accountTypes']);

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::put('/auth/profile', [AuthController::class, 'updateProfile']);
        Route::patch('/auth/account-type', [AuthController::class, 'updateAccountType']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);

        Route::get('/favorites', [FavoriteController::class, 'index']);
        Route::post('/favorites', [FavoriteController::class, 'store']);
        Route::delete('/favorites/{listing}', [FavoriteController::class, 'destroy']);

        Route::get('/my/listings', [UserListingController::class, 'index']);
        Route::get('/my/listings/allowed-categories', [UserListingController::class, 'allowedCategories']);
        Route::get('/my/listings/{listing}', [UserListingController::class, 'show']);
        Route::post('/listings', [UserListingController::class, 'store']);
        Route::post('/listings/{listing}/media', [UserListingController::class, 'uploadMedia']);
        Route::patch('/listings/{listing}/media/{media}/cover', [UserListingController::class, 'setMediaCover']);
        Route::delete('/listings/{listing}/media/{media}', [UserListingController::class, 'deleteMedia']);
        Route::put('/listings/{listing}', [UserListingController::class, 'update']);
        Route::patch('/listings/{listing}', [UserListingController::class, 'update']);
        Route::delete('/listings/{listing}', [UserListingController::class, 'destroy']);
        Route::post('/listings/{listing}/availability', [AvailabilitySlotController::class, 'store']);
        Route::put('/availability/{slot}', [AvailabilitySlotController::class, 'update']);
        Route::patch('/availability/{slot}', [AvailabilitySlotController::class, 'update']);
        Route::delete('/availability/{slot}', [AvailabilitySlotController::class, 'destroy']);

        Route::get('/my/wanted-requests', [WantedRequestController::class, 'myIndex']);
        Route::get('/my/wanted-requests/{wantedRequest}', [WantedRequestController::class, 'show']);
        Route::post('/wanted-requests', [WantedRequestController::class, 'store']);
        Route::put('/wanted-requests/{wantedRequest}', [WantedRequestController::class, 'update']);
        Route::patch('/wanted-requests/{wantedRequest}', [WantedRequestController::class, 'update']);
        Route::delete('/wanted-requests/{wantedRequest}', [WantedRequestController::class, 'destroy']);

        Route::get('/booking-requests', [BookingRequestController::class, 'index']);
        Route::post('/booking-requests', [BookingRequestController::class, 'store']);
        Route::get('/my-booking-requests', [BookingRequestController::class, 'index']);
        Route::post('/listings/{listing}/booking-request', [BookingRequestController::class, 'storeForListing']);
        Route::get('/owner-booking-requests', [BookingRequestController::class, 'ownerIndex']);
        Route::put('/booking-requests/{bookingRequest}/accept', [BookingRequestController::class, 'accept']);
        Route::put('/booking-requests/{bookingRequest}/reject', [BookingRequestController::class, 'reject']);
        Route::get('/booking-requests/{bookingRequest}', [BookingRequestController::class, 'show']);
        Route::patch('/booking-requests/{bookingRequest}/cancel', [BookingRequestController::class, 'cancel']);
        Route::get('/owner/dashboard', [BookingRequestController::class, 'ownerDashboard']);
        Route::get('/owner/booking-requests', [BookingRequestController::class, 'ownerIndex']);
        Route::patch('/owner/booking-requests/{bookingRequest}', [BookingRequestController::class, 'ownerUpdate']);

        Route::get('/verification-requests', [VerificationRequestController::class, 'index']);
        Route::post('/verification-requests', [VerificationRequestController::class, 'store']);
        Route::get('/verification-requests/{verificationRequest}', [VerificationRequestController::class, 'show']);
        Route::put('/verification-requests/{verificationRequest}', [VerificationRequestController::class, 'update']);
        Route::patch('/verification-requests/{verificationRequest}', [VerificationRequestController::class, 'update']);
    });

    Route::prefix('admin')->group(function (): void {
        Route::post('/login', [AdminAuthController::class, 'login']);

        Route::middleware(['auth:sanctum', 'admin'])->group(function (): void {
            Route::get('/me', [AdminAuthController::class, 'me']);
            Route::post('/logout', [AdminAuthController::class, 'logout']);
            Route::get('/dashboard', AdminDashboardController::class);
            Route::apiResource('categories', AdminCategoryController::class);
            Route::apiResource('categories.filters', AdminCategoryFilterController::class)
                ->except(['show']);
            Route::apiResource('listings', AdminListingController::class);
            Route::patch('/listings/{listing}/status', [AdminListingController::class, 'status']);
            Route::apiResource('users', AdminUserController::class)->only(['index', 'update', 'destroy']);
            Route::apiResource('booking-requests', AdminBookingRequestController::class)
                ->parameters(['booking-requests' => 'bookingRequest'])
                ->only(['index', 'update', 'destroy']);
            Route::put('/booking-requests/{bookingRequest}/accept', [AdminBookingRequestController::class, 'accept']);
            Route::put('/booking-requests/{bookingRequest}/reject', [AdminBookingRequestController::class, 'reject']);
            Route::apiResource('availability-slots', AdminAvailabilitySlotController::class)
                ->parameters(['availability-slots' => 'availabilitySlot'])
                ->only(['index', 'store', 'update', 'destroy']);
            Route::get('/cities', [AdminLocationController::class, 'cities']);
            Route::post('/cities', [AdminLocationController::class, 'storeCity']);
            Route::put('/cities/{city}', [AdminLocationController::class, 'updateCity']);
            Route::delete('/cities/{city}', [AdminLocationController::class, 'destroyCity']);
            Route::get('/city-areas', [AdminLocationController::class, 'areas']);
            Route::post('/city-areas', [AdminLocationController::class, 'storeArea']);
            Route::put('/city-areas/{area}', [AdminLocationController::class, 'updateArea']);
            Route::delete('/city-areas/{area}', [AdminLocationController::class, 'destroyArea']);
            Route::get('/settings', [AdminSettingController::class, 'index']);
            Route::put('/settings', [AdminSettingController::class, 'update']);
            Route::apiResource('banners', AdminBannerController::class)->only(['index', 'store', 'update', 'destroy']);
            Route::apiResource('verification-requests', AdminVerificationRequestController::class)
                ->only(['index', 'show', 'update']);
        });
    });
});
