<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\BookingRequestController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CategoryFilterController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ListingController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin');
Route::redirect('/login', '/admin/login')->name('login');

Route::prefix('admin')->name('admin.')->group(function (): void {
    Route::middleware('guest')->group(function (): void {
        Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
        Route::post('/login', [AuthController::class, 'login'])->name('login.store');
    });

    Route::middleware(['auth', 'admin'])->group(function (): void {
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('/', DashboardController::class)->name('dashboard');
        Route::resource('categories', CategoryController::class)->except(['show']);
        Route::resource('categories.filters', CategoryFilterController::class)->except(['show', 'create']);
        Route::resource('listings', ListingController::class)->except(['show']);
        Route::resource('booking-requests', BookingRequestController::class)->only(['index', 'edit', 'update']);
    });
});
