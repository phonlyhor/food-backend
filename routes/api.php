<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\api\CategoriesController;
use App\Http\Controllers\api\ProductController;
use App\Http\Controllers\api\BannerController;
use App\Http\Controllers\api\PromotionController;
use App\Http\Controllers\api\CouponController;
use App\Http\Controllers\api\EventController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\api\AdminSpinController;
use App\Http\Controllers\SpinController;
use App\Http\Controllers\api\OrderController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PaymentController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// ---------------------------------------------------------------------
// Public auth routes
// ---------------------------------------------------------------------
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register_user']);
Route::post('/payment/callback', [PaymentController::class, 'ipnCallback']);

// ---------------------------------------------------------------------
// Public read-only routes (anyone can browse the storefront)
// ---------------------------------------------------------------------
Route::get('/categories', [CategoriesController::class, 'index']);
Route::get('/categories/{category}', [CategoriesController::class, 'show']);

Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{product}', [ProductController::class, 'show']);

Route::get('/banners', [BannerController::class, 'index']);
Route::get('/banners/{banner}', [BannerController::class, 'show']);

Route::get('/promotions', [PromotionController::class, 'index']);
Route::get('/promotions/{promotion}', [PromotionController::class, 'show']);

Route::get('/coupons', [CouponController::class, 'index']);
Route::get('/coupons/{coupon}', [CouponController::class, 'show']);

Route::get('/events', [EventController::class, 'index']);
Route::get('/events/{event}', [EventController::class, 'show']);

// ---------------------------------------------------------------------
// Authenticated routes (any logged-in user)
// ---------------------------------------------------------------------
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});

// ---------------------------------------------------------------------
// Customer-only routes
// ---------------------------------------------------------------------
Route::middleware(['auth:sanctum', 'ability:user'])->group(function () {
    // Favorites
    Route::get('/favorites', [FavoriteController::class, 'index']);
    Route::post('/favorites', [FavoriteController::class, 'store']);
    Route::delete('/favorites/{id}', [FavoriteController::class, 'destroy']);

    // Spin Wheel Routes
    Route::get('/prizes', [SpinController::class, 'prizes']);
    Route::post('/spin', [SpinController::class, 'spin']);
    Route::get('/spin/history', [SpinController::class, 'history']);
    Route::get('/spin/status', [SpinController::class, 'status']);

    // Orders & Coupons validation
    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/orders/{id}', [OrderController::class, 'show']);
    Route::post('/orders', [OrderController::class, 'store']);
    Route::post('/coupons/validate', [OrderController::class, 'validateCoupon']);

    // User Profile Actions
    Route::post('/user/profile', [UserController::class, 'updateProfile']);
    Route::put('/user/password', [UserController::class, 'changePassword']);

    // Payments
    Route::post('/payment/initiate', [PaymentController::class, 'initiatePayment']);
    Route::get('/payment/status/{orderId}', [PaymentController::class, 'checkPaymentStatus']);
});

// ---------------------------------------------------------------------
// Admin-only routes
// ---------------------------------------------------------------------
Route::middleware(['auth:sanctum', 'ability:admin'])->group(function () {
    Route::resource('categories', CategoriesController::class)->except(['index', 'show']);
    Route::resource('products', ProductController::class)->except(['index', 'show']);
    Route::resource('banners', BannerController::class)->except(['index', 'show']);
    Route::resource('promotions', PromotionController::class)->except(['index', 'show']);
    Route::resource('coupons', CouponController::class)->except(['index', 'show']);
    Route::resource('events', EventController::class)->except(['index', 'show']);
    
    // Admin Spin Management
    Route::get('/admin/spin-history', [AdminSpinController::class, 'history']);
    Route::apiResource('spin-prizes', AdminSpinController::class);

    // Admin Order Management
    Route::get('/admin/orders', [OrderController::class, 'adminIndex']);
    Route::get('/admin/orders/{id}', [OrderController::class, 'adminShow']);
    Route::put('/admin/orders/{id}', [OrderController::class, 'update']);
    Route::get('/admin/dashboard-stats', [OrderController::class, 'dashboardStats']);
    Route::get('/admin/reports-stats', [OrderController::class, 'reportsStats']);
    
    // Admin Customer Management
    Route::get('/admin/customers', [UserController::class, 'adminIndex']);
});