<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\BlogPostController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\Api\SocialAuthController;

/*
|--------------------------------------------------------------------------
| API Routes - Envisage Marketplace
|--------------------------------------------------------------------------
| Authentication: All protected routes use Laravel Sanctum (auth:sanctum)
| Authorization: Seller/Admin routes use CheckRole middleware (role:admin,seller)
*/

// ==================== PUBLIC ROUTES ====================

// Health check
Route::get('/test', function() {
    return response()->json(['message' => 'API is working', 'version' => '1.0']);
});

// Authentication (rate limited to prevent brute force)
Route::middleware('throttle:5,1')->group(function() {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    
    // Social Authentication
    Route::get('/auth/facebook', [SocialAuthController::class, 'redirectToFacebook']);
    Route::get('/auth/facebook/callback', [SocialAuthController::class, 'handleFacebookCallback']);
    Route::get('/auth/google', [SocialAuthController::class, 'redirectToGoogle']);
    Route::get('/auth/google/callback', [SocialAuthController::class, 'handleGoogleCallback']);
});

// Public product browsing (rate limited to prevent scraping)
Route::middleware('throttle:60,1')->group(function() {
    Route::get('/products', [ProductController::class, 'index']); // List products with search/filters
    Route::get('/products/{id}', [ProductController::class, 'show']); // View single product
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/categories/{id}', [CategoryController::class, 'show']);
});

// ==================== AUTHENTICATED ROUTES ====================

Route::middleware(['auth:sanctum', 'throttle:120,1'])->group(function () {
    
    // Auth user info
    Route::get('/user', [AuthController::class, 'getUser']);
    Route::post('/logout', [AuthController::class, 'logout']);
    
    // ========== USER PROFILE ==========
    Route::get('/user/profile', [UserController::class, 'profile']);
    Route::put('/user/profile', [UserController::class, 'updateProfile']);
    Route::post('/user/avatar', [UserController::class, 'uploadAvatar']);
    Route::put('/user/password', [UserController::class, 'changePassword']);
    Route::delete('/user/account', [UserController::class, 'deleteAccount']);
    
    // ========== FAVORITES ==========
    Route::get('/favorites', [FavoriteController::class, 'index']); // Get user's favorites
    Route::post('/favorites/{productId}', [FavoriteController::class, 'store']); // Add to favorites
    Route::delete('/favorites/{productId}', [FavoriteController::class, 'destroy']); // Remove from favorites
    Route::get('/favorites/check/{productId}', [FavoriteController::class, 'check']); // Check if favorited
    
    // ========== CART ==========
    Route::get('/cart', [CartController::class, 'index']); // Get cart with total
    Route::post('/cart', [CartController::class, 'store']); // Add to cart
    Route::put('/cart/{id}', [CartController::class, 'update']); // Update quantity
    Route::delete('/cart/{id}', [CartController::class, 'destroy']); // Remove item
    Route::delete('/cart', [CartController::class, 'clear']); // Clear cart
    
    // ========== ORDERS ==========
    Route::get('/orders', [OrderController::class, 'index']); // Get user's orders with filters
    Route::get('/orders/{id}', [OrderController::class, 'show']); // View order details
    Route::post('/checkout', [OrderController::class, 'checkout']); // Create order from cart
    Route::put('/orders/{id}/cancel', [OrderController::class, 'cancel']); // Cancel order
    Route::get('/orders/statistics/overview', [OrderController::class, 'statistics']); // Get order statistics
    
    // ========== SELLER/ADMIN ROUTES - PRODUCTS ==========
    Route::middleware('role:admin,seller')->group(function () {
        Route::post('/products', [ProductController::class, 'store']); // Create product
        Route::put('/products/{id}', [ProductController::class, 'update']); // Update product
        Route::delete('/products/{id}', [ProductController::class, 'destroy']); // Delete product
        Route::get('/seller/products', [ProductController::class, 'myProducts']); // Get seller's products
        
        // Seller order management
        Route::get('/seller/orders', [OrderController::class, 'sellerOrders']); // Get seller's orders
        Route::put('/orders/{id}/status', [OrderController::class, 'updateStatus']); // Update order status
    });
    
    // ========== ROLE & PERMISSION MANAGEMENT (ADMIN ONLY) ==========
    Route::middleware('role:admin')->group(function () {
        Route::post('/users/{userId}/assign-role', [RoleController::class, 'assignRole']);
        Route::post('/users/{userId}/remove-role', [RoleController::class, 'removeRole']);
        Route::post('/users/{userId}/give-permission', [RoleController::class, 'givePermission']);
        Route::post('/users/{userId}/revoke-permission', [RoleController::class, 'revokePermission']);
        
        // Admin dashboard
        Route::get('/admin/overview', [\App\Http\Controllers\AdminController::class, 'getOverview']);
        Route::get('/admin/users', [\App\Http\Controllers\AdminController::class, 'getUsers']);
        Route::post('/admin/users', [\App\Http\Controllers\AdminController::class, 'createUser']);
        Route::put('/admin/users/{id}', [\App\Http\Controllers\AdminController::class, 'updateUser']);
        Route::delete('/admin/users/{id}', [\App\Http\Controllers\AdminController::class, 'deleteUser']);
        Route::get('/admin/statistics', [\App\Http\Controllers\AdminController::class, 'getStatistics']);
        
        // Admin Settings Management
        Route::get('/admin/settings', [\App\Http\Controllers\Admin\SettingsController::class, 'index']);
        Route::post('/admin/settings/batch', [\App\Http\Controllers\Admin\SettingsController::class, 'updateBatch']);
        Route::put('/admin/settings/{key}', [\App\Http\Controllers\Admin\SettingsController::class, 'update']);
        Route::delete('/admin/settings/{key}', [\App\Http\Controllers\Admin\SettingsController::class, 'destroy']);
        Route::match(['get', 'post'], '/admin/settings/initialize', [\App\Http\Controllers\Admin\SettingsController::class, 'initializeDefaults']);
    });
    
    // ========== EXISTING ROUTES (Legacy Support) ==========
    
    // Seller routes (Protected - must be authenticated)
    Route::middleware('role:admin,seller')->group(function () {
        Route::get('/seller/listings', [\App\Http\Controllers\SellerController::class, 'getListings']);
        Route::get('/seller/analytics', [\App\Http\Controllers\SellerController::class, 'getAnalytics']);
    });
    
    // Review routes
    Route::post('/products/{productId}/reviews', [\App\Http\Controllers\ReviewController::class, 'store']);
    Route::get('/products/{productId}/reviews', [\App\Http\Controllers\ReviewController::class, 'index']);
    
    // Notification routes
    Route::get('/notifications', [\App\Http\Controllers\NotificationController::class, 'index']);
    Route::post('/notifications/{id}/read', [\App\Http\Controllers\NotificationController::class, 'markAsRead']);
    
    // Blog posts (Protected)
    Route::post('/blog-posts', [BlogPostController::class, 'store']);
    Route::put('/blog-posts/{id}', [BlogPostController::class, 'update']);
    Route::delete('/blog-posts/{id}', [BlogPostController::class, 'destroy']);
    
    // ========== PAYMENT ROUTES ==========
    Route::post('/payments/create-intent', [PaymentController::class, 'createPaymentIntent']); // Create Stripe payment intent
    Route::post('/payments/confirm', [PaymentController::class, 'confirmPayment']); // Confirm payment success
    Route::get('/payments/my-payments', [PaymentController::class, 'myPayments']); // Get user's payment history
    Route::post('/payments/{id}/refund', [PaymentController::class, 'requestRefund']); // Request refund
    
    // Legacy payment routes
    Route::post('/payments', [PaymentController::class, 'store']);
    Route::put('/payments/{id}', [PaymentController::class, 'update']);
    Route::delete('/payments/{id}', [PaymentController::class, 'destroy']);
});

// ========== STRIPE WEBHOOK (No Auth Required) ==========
Route::post('/webhooks/stripe', [PaymentController::class, 'handleWebhook']);

// ========== SEO ROUTES (Public) ==========
Route::get('/sitemap.xml', [\App\Http\Controllers\SeoController::class, 'sitemap']);
Route::get('/robots.txt', [\App\Http\Controllers\SeoController::class, 'robots']);
Route::get('/meta', [\App\Http\Controllers\SeoController::class, 'meta']);
Route::get('/structured-data', [\App\Http\Controllers\SeoController::class, 'structuredData']);

// ========== PUBLIC SETTINGS ==========
Route::get('/settings/public', [\App\Http\Controllers\Admin\SettingsController::class, 'public']);

// ========== SETUP ROUTE (First-time initialization - disable after setup) ==========
Route::match(['get', 'post'], '/setup/initialize-settings', [\App\Http\Controllers\Admin\SettingsController::class, 'initializeDefaults']);

// ========== PUBLIC LEGACY ROUTES ==========
Route::get('/reviews', [\App\Http\Controllers\ReviewController::class, 'getAllReviews']);
Route::get('/blog-posts', [BlogPostController::class, 'index']);
Route::get('/blog-posts/{id}', [BlogPostController::class, 'show']);
Route::get('/payments', [PaymentController::class, 'index']);
Route::get('/payments/{id}', [PaymentController::class, 'show']);
