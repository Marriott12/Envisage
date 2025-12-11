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

Route::get('/wishlists/shared/{token}', [\App\Http\Controllers\WishlistController::class, 'getShared']);

// Checkout routes
Route::post('/checkout/validate-promo-code', [\App\Http\Controllers\CheckoutController::class, 'validatePromoCode']);
Route::post('/checkout/guest-session', [\App\Http\Controllers\CheckoutController::class, 'createGuestSession']);
Route::get('/checkout/guest-session/{token}', [\App\Http\Controllers\CheckoutController::class, 'getGuestSession']);
Route::post('/checkout/shipping-rates', [\App\Http\Controllers\CheckoutController::class, 'getShippingRates']);
Route::post('/checkout/calculate-total', [\App\Http\Controllers\CheckoutController::class, 'calculateOrderTotal']);

// Recommendation routes
Route::post('/products/{productId}/track-view', [\App\Http\Controllers\RecommendationController::class, 'trackView']);
Route::get('/recommendations', [\App\Http\Controllers\RecommendationController::class, 'getRecommendations']);
Route::get('/recommendations/trending', [\App\Http\Controllers\RecommendationController::class, 'getTrending']);
Route::get('/recommendations/new-arrivals', [\App\Http\Controllers\RecommendationController::class, 'getNewArrivals']);
Route::get('/products/{productId}/also-viewed', [\App\Http\Controllers\RecommendationController::class, 'getAlsoViewed']);
Route::get('/products/{productId}/also-bought', [\App\Http\Controllers\RecommendationController::class, 'getAlsoBought']);
Route::get('/products/{productId}/similar', [\App\Http\Controllers\RecommendationController::class, 'getSimilarProducts']);
Route::middleware('auth:sanctum')->get('/recommendations/history-based', [\App\Http\Controllers\RecommendationController::class, 'getHistoryBased']);

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
    Route::get('/products/{productId}/reviews', [\App\Http\Controllers\ReviewController::class, 'index']);
    Route::post('/products/{productId}/reviews', [\App\Http\Controllers\ReviewController::class, 'store']);
    Route::put('/products/{productId}/reviews/{reviewId}', [\App\Http\Controllers\ReviewController::class, 'update']);
    Route::delete('/products/{productId}/reviews/{reviewId}', [\App\Http\Controllers\ReviewController::class, 'destroy']);
    Route::post('/products/{productId}/reviews/{reviewId}/helpful', [\App\Http\Controllers\ReviewController::class, 'markHelpful']);
    Route::get('/products/{productId}/reviews/user', [\App\Http\Controllers\ReviewController::class, 'getUserReview']);
    Route::get('/products/{productId}/can-review', [\App\Http\Controllers\ReviewController::class, 'canReview']);
    
    // Wishlist routes
    Route::get('/wishlists', [\App\Http\Controllers\WishlistController::class, 'index']);
    Route::post('/wishlists', [\App\Http\Controllers\WishlistController::class, 'store']);
    Route::get('/wishlists/{id}', [\App\Http\Controllers\WishlistController::class, 'show']);
    Route::put('/wishlists/{id}', [\App\Http\Controllers\WishlistController::class, 'update']);
    Route::delete('/wishlists/{id}', [\App\Http\Controllers\WishlistController::class, 'destroy']);
    Route::post('/wishlists/{id}/items', [\App\Http\Controllers\WishlistController::class, 'addItem']);
    Route::put('/wishlists/{wishlistId}/items/{itemId}', [\App\Http\Controllers\WishlistController::class, 'updateItem']);
    Route::delete('/wishlists/{wishlistId}/items/{itemId}', [\App\Http\Controllers\WishlistController::class, 'removeItem']);
    
    // Price alerts
    Route::get('/price-alerts', [\App\Http\Controllers\WishlistController::class, 'getPriceAlerts']);
    Route::post('/price-alerts', [\App\Http\Controllers\WishlistController::class, 'setPriceAlert']);
    Route::delete('/price-alerts/{id}', [\App\Http\Controllers\WishlistController::class, 'deletePriceAlert']);
    
    // Notification routes
    Route::get('/notifications', [\App\Http\Controllers\NotificationController::class, 'index']);
    Route::post('/notifications/{id}/read', [\App\Http\Controllers\NotificationController::class, 'markAsRead']);
    
    // Profile routes
    Route::get('/profile', [\App\Http\Controllers\ProfileController::class, 'show']);
    Route::put('/profile', [\App\Http\Controllers\ProfileController::class, 'update']);
    Route::post('/profile/avatar', [\App\Http\Controllers\ProfileController::class, 'uploadAvatar']);
    Route::post('/profile/change-password', [\App\Http\Controllers\ProfileController::class, 'changePassword']);
    
    // Two-factor authentication
    Route::post('/profile/2fa/enable', [\App\Http\Controllers\ProfileController::class, 'enableTwoFactor']);
    Route::post('/profile/2fa/confirm', [\App\Http\Controllers\ProfileController::class, 'confirmTwoFactor']);
    Route::post('/profile/2fa/disable', [\App\Http\Controllers\ProfileController::class, 'disableTwoFactor']);
    
    // Notification preferences
    Route::get('/profile/notification-preferences', [\App\Http\Controllers\ProfileController::class, 'getNotificationPreferences']);
    Route::put('/profile/notification-preferences', [\App\Http\Controllers\ProfileController::class, 'updateNotificationPreferences']);
    
    // Shipping addresses
    Route::get('/profile/shipping-addresses', [\App\Http\Controllers\ProfileController::class, 'getShippingAddresses']);
    Route::post('/profile/shipping-addresses', [\App\Http\Controllers\ProfileController::class, 'createShippingAddress']);
    Route::put('/profile/shipping-addresses/{id}', [\App\Http\Controllers\ProfileController::class, 'updateShippingAddress']);
    Route::delete('/profile/shipping-addresses/{id}', [\App\Http\Controllers\ProfileController::class, 'deleteShippingAddress']);
    
    // Security sessions
    Route::get('/profile/sessions', [\App\Http\Controllers\ProfileController::class, 'getActiveSessions']);
    Route::post('/profile/sessions/log', [\App\Http\Controllers\ProfileController::class, 'logSession']);
    
    // Seller Analytics (Seller/Admin only)
    Route::middleware('role:seller,admin')->group(function() {
        Route::get('/analytics/dashboard', [\App\Http\Controllers\AnalyticsController::class, 'sellerDashboard']);
        Route::get('/analytics/customers', [\App\Http\Controllers\AnalyticsController::class, 'customerAnalytics']);
        Route::get('/analytics/products/{productId}', [\App\Http\Controllers\AnalyticsController::class, 'productPerformance']);
        Route::get('/analytics/export', [\App\Http\Controllers\AnalyticsController::class, 'exportData']);
    });
    
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

// ========== NEW FEATURES ROUTES ==========

// Messaging System
Route::middleware('auth:sanctum')->prefix('messages')->group(function() {
    Route::get('/conversations', [\App\Http\Controllers\Api\MessagingController::class, 'conversations']);
    Route::get('/conversations/{id}', [\App\Http\Controllers\Api\MessagingController::class, 'show']);
    Route::post('/conversations/start', [\App\Http\Controllers\Api\MessagingController::class, 'start']);
    Route::post('/conversations/{id}/messages', [\App\Http\Controllers\Api\MessagingController::class, 'sendMessage']);
    Route::post('/conversations/{id}/mark-read', [\App\Http\Controllers\Api\MessagingController::class, 'markAsRead']);
    Route::get('/unread-count', [\App\Http\Controllers\Api\MessagingController::class, 'unreadCount']);
});

// Product Q&A
Route::prefix('products/{productId}')->group(function() {
    Route::get('/questions', [\App\Http\Controllers\Api\ProductQuestionController::class, 'index']);
    Route::post('/questions', [\App\Http\Controllers\Api\ProductQuestionController::class, 'store'])->middleware('auth:sanctum');
});
Route::middleware('auth:sanctum')->prefix('questions')->group(function() {
    Route::post('/{questionId}/answers', [\App\Http\Controllers\Api\ProductQuestionController::class, 'storeAnswer']);
    Route::post('/{questionId}/upvote', [\App\Http\Controllers\Api\ProductQuestionController::class, 'upvote']);
    Route::post('/answers/{answerId}/helpful', [\App\Http\Controllers\Api\ProductQuestionController::class, 'markHelpful']);
});

// Disputes & Returns
Route::middleware('auth:sanctum')->prefix('orders/{orderId}')->group(function() {
    Route::post('/disputes', [\App\Http\Controllers\Api\DisputeController::class, 'createDispute']);
    Route::post('/returns', [\App\Http\Controllers\Api\DisputeController::class, 'createReturn']);
});
Route::middleware('auth:sanctum')->prefix('disputes')->group(function() {
    Route::get('/', [\App\Http\Controllers\Api\DisputeController::class, 'listDisputes']);
    Route::put('/{disputeId}', [\App\Http\Controllers\Api\DisputeController::class, 'updateDispute']);
});
Route::middleware('auth:sanctum')->prefix('returns')->group(function() {
    Route::get('/', [\App\Http\Controllers\Api\DisputeController::class, 'listReturns']);
    Route::put('/{returnId}/approve', [\App\Http\Controllers\Api\DisputeController::class, 'approveReturn']);
    Route::put('/{returnId}/tracking', [\App\Http\Controllers\Api\DisputeController::class, 'updateReturnTracking']);
    Route::post('/{returnId}/confirm', [\App\Http\Controllers\Api\DisputeController::class, 'confirmReturn']);
});

// Subscriptions
Route::prefix('subscriptions')->group(function() {
    Route::get('/plans', [\App\Http\Controllers\Api\SubscriptionController::class, 'plans']);
    Route::middleware('auth:sanctum')->group(function() {
        Route::get('/current', [\App\Http\Controllers\Api\SubscriptionController::class, 'currentSubscription']);
        Route::post('/subscribe', [\App\Http\Controllers\Api\SubscriptionController::class, 'subscribe']);
        Route::post('/cancel', [\App\Http\Controllers\Api\SubscriptionController::class, 'cancel']);
        Route::post('/feature-product', [\App\Http\Controllers\Api\SubscriptionController::class, 'featureProduct']);
    });
    Route::post('/webhook', [\App\Http\Controllers\Api\SubscriptionController::class, 'webhookHandler']);
});

// Loyalty & Rewards
Route::middleware('auth:sanctum')->prefix('loyalty')->group(function() {
    Route::get('/points', [\App\Http\Controllers\Api\LoyaltyController::class, 'myPoints']);
    Route::get('/transactions', [\App\Http\Controllers\Api\LoyaltyController::class, 'transactions']);
    Route::get('/rewards', [\App\Http\Controllers\Api\LoyaltyController::class, 'rewardsCatalog']);
    Route::post('/redeem', [\App\Http\Controllers\Api\LoyaltyController::class, 'redeemReward']);
    Route::get('/redemptions', [\App\Http\Controllers\Api\LoyaltyController::class, 'myRedemptions']);
    Route::get('/referral-code', [\App\Http\Controllers\Api\LoyaltyController::class, 'getReferralCode']);
    Route::get('/referrals', [\App\Http\Controllers\Api\LoyaltyController::class, 'myReferrals']);
    Route::post('/apply-referral', [\App\Http\Controllers\Api\LoyaltyController::class, 'applyReferralCode']);
});

// Flash Sales
Route::prefix('flash-sales')->group(function() {
    Route::get('/', [\App\Http\Controllers\Api\FlashSaleController::class, 'index']);
    Route::get('/{id}', [\App\Http\Controllers\Api\FlashSaleController::class, 'show']);
    Route::middleware('auth:sanctum')->group(function() {
        Route::post('/', [\App\Http\Controllers\Api\FlashSaleController::class, 'create']);
        Route::post('/products/{flashSaleProductId}/purchase', [\App\Http\Controllers\Api\FlashSaleController::class, 'purchase']);
        Route::get('/my/purchases', [\App\Http\Controllers\Api\FlashSaleController::class, 'myPurchases']);
        Route::post('/{id}/end', [\App\Http\Controllers\Api\FlashSaleController::class, 'endSale']);
    });
});

// Product Bundles
Route::prefix('bundles')->group(function() {
    Route::get('/', [\App\Http\Controllers\Api\BundleController::class, 'index']);
    Route::get('/{id}', [\App\Http\Controllers\Api\BundleController::class, 'show']);
    Route::middleware('auth:sanctum')->group(function() {
        Route::post('/', [\App\Http\Controllers\Api\BundleController::class, 'create']);
        Route::put('/{id}', [\App\Http\Controllers\Api\BundleController::class, 'update']);
        Route::delete('/{id}', [\App\Http\Controllers\Api\BundleController::class, 'delete']);
    });
});
Route::get('/products/{productId}/frequently-bought', [\App\Http\Controllers\Api\BundleController::class, 'frequentlyBoughtTogether']);

// Inventory Management
Route::middleware('auth:sanctum')->prefix('inventory')->group(function() {
    Route::put('/products/{productId}/stock', [\App\Http\Controllers\Api\InventoryController::class, 'updateStock']);
    Route::get('/products/{productId}/history', [\App\Http\Controllers\Api\InventoryController::class, 'inventoryHistory']);
    Route::get('/low-stock-alerts', [\App\Http\Controllers\Api\InventoryController::class, 'lowStockAlerts']);
    Route::post('/products/{productId}/low-stock-threshold', [\App\Http\Controllers\Api\InventoryController::class, 'setLowStockThreshold']);
    Route::post('/import', [\App\Http\Controllers\Api\InventoryController::class, 'importProducts']);
    Route::get('/import/{importId}/status', [\App\Http\Controllers\Api\InventoryController::class, 'importStatus']);
    Route::get('/export', [\App\Http\Controllers\Api\InventoryController::class, 'exportProducts']);
    Route::post('/bulk-update-prices', [\App\Http\Controllers\Api\InventoryController::class, 'bulkUpdatePrices']);
});

// Abandoned Cart Recovery
Route::middleware('auth:sanctum')->prefix('abandoned-carts')->group(function() {
    Route::post('/track', [\App\Http\Controllers\Api\AbandonedCartController::class, 'track']);
    Route::get('/list', [\App\Http\Controllers\Api\AbandonedCartController::class, 'list']);
    Route::get('/stats', [\App\Http\Controllers\Api\AbandonedCartController::class, 'stats']);
});
Route::get('/abandoned-carts/recover/{token}', [\App\Http\Controllers\Api\AbandonedCartController::class, 'recover']);
Route::get('/abandoned-carts/email/{emailId}/open', [\App\Http\Controllers\Api\AbandonedCartController::class, 'trackEmailOpen']);
Route::get('/abandoned-carts/email/{emailId}/click', [\App\Http\Controllers\Api\AbandonedCartController::class, 'trackEmailClick']);

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

// ========== ADMIN ROUTES ==========
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function() {
    
    // Dispute Management
    Route::get('/disputes', [\App\Http\Controllers\Api\AdminController::class, 'disputes']);
    Route::put('/disputes/{id}/update', [\App\Http\Controllers\Api\AdminController::class, 'updateDispute']);
    
    // Flash Sale Management
    Route::get('/flash-sales', [\App\Http\Controllers\Api\AdminController::class, 'flashSales']);
    
    // Analytics
    Route::get('/analytics', [\App\Http\Controllers\Api\AdminController::class, 'analytics']);
    Route::get('/analytics/export', [\App\Http\Controllers\Api\AdminController::class, 'exportAnalytics']);
    
    // Subscription Plan Management
    Route::get('/subscription-plans', [\App\Http\Controllers\Api\AdminController::class, 'subscriptionPlans']);
    Route::post('/subscription-plans', [\App\Http\Controllers\Api\AdminController::class, 'createPlan']);
    Route::put('/subscription-plans/{id}', [\App\Http\Controllers\Api\AdminController::class, 'updatePlan']);
    Route::delete('/subscription-plans/{id}', [\App\Http\Controllers\Api\AdminController::class, 'deletePlan']);
    
    // Inventory Management
    Route::get('/inventory', [\App\Http\Controllers\Api\InventoryController::class, 'index']);
    Route::get('/inventory/alerts', [\App\Http\Controllers\Api\InventoryController::class, 'alerts']);
    Route::post('/inventory/{id}/restock', [\App\Http\Controllers\Api\InventoryController::class, 'restock']);
    Route::put('/inventory/{id}/threshold', [\App\Http\Controllers\Api\InventoryController::class, 'updateThreshold']);
    
    // User Management
    Route::get('/users', [\App\Http\Controllers\Api\UserManagementController::class, 'index']);
    Route::put('/users/{id}/role', [\App\Http\Controllers\Api\UserManagementController::class, 'updateRole']);
    Route::put('/users/{id}/status', [\App\Http\Controllers\Api\UserManagementController::class, 'updateStatus']);
    
    // Reporting
    Route::get('/reports', [\App\Http\Controllers\Api\ReportController::class, 'index']);
    Route::post('/reports/generate', [\App\Http\Controllers\Api\ReportController::class, 'generate']);
    Route::get('/reports/{id}/download', [\App\Http\Controllers\Api\ReportController::class, 'download']);
    
    // Refund Management
    Route::get('/refunds', [\App\Http\Controllers\Api\RefundController::class, 'index']);
    Route::put('/refunds/{id}/process', [\App\Http\Controllers\Api\RefundController::class, 'process']);
    
    // Featured Products
    Route::get('/products/available-for-featuring', [\App\Http\Controllers\Api\FeaturedProductController::class, 'availableProducts']);
    Route::get('/products/featured', [\App\Http\Controllers\Api\FeaturedProductController::class, 'featured']);
    Route::post('/products/{id}/feature', [\App\Http\Controllers\Api\FeaturedProductController::class, 'feature']);
    Route::delete('/products/{id}/unfeature', [\App\Http\Controllers\Api\FeaturedProductController::class, 'unfeature']);
    
    // Loyalty Tiers
    Route::get('/loyalty-tiers', [\App\Http\Controllers\Api\LoyaltyTierController::class, 'index']);
    Route::post('/loyalty-tiers', [\App\Http\Controllers\Api\LoyaltyTierController::class, 'store']);
    Route::put('/loyalty-tiers/{id}', [\App\Http\Controllers\Api\LoyaltyTierController::class, 'update']);
    Route::delete('/loyalty-tiers/{id}', [\App\Http\Controllers\Api\LoyaltyTierController::class, 'destroy']);
    Route::get('/loyalty-tiers/stats', [\App\Http\Controllers\Api\LoyaltyTierController::class, 'stats']);
});

// ==================== PAYMENT ROUTES ====================
Route::prefix('payments')->middleware('auth:sanctum')->group(function () {
    Route::post('/create-intent', [\App\Http\Controllers\Api\PaymentController::class, 'createIntent']);
    Route::get('/methods', [\App\Http\Controllers\Api\PaymentController::class, 'getPaymentMethods']);
    Route::post('/methods', [\App\Http\Controllers\Api\PaymentController::class, 'savePaymentMethod']);
    Route::delete('/methods/{id}', [\App\Http\Controllers\Api\PaymentController::class, 'deletePaymentMethod']);
    Route::post('/methods/{id}/set-default', [\App\Http\Controllers\Api\PaymentController::class, 'setDefaultPaymentMethod']);
    Route::post('/split-payment', [\App\Http\Controllers\Api\PaymentController::class, 'processSplitPayment']);
});

Route::post('/payments/webhook', [\App\Http\Controllers\Api\PaymentController::class, 'webhook']); // No auth for webhook

// ==================== GIFT CARDS & VOUCHERS ====================
Route::prefix('gift-cards')->group(function () {
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/', [\App\Http\Controllers\Api\GiftCardController::class, 'purchase']);
        Route::get('/my-cards', [\App\Http\Controllers\Api\GiftCardController::class, 'myCards']);
    });
    Route::post('/validate', [\App\Http\Controllers\Api\GiftCardController::class, 'validateCard']);
    Route::post('/apply', [\App\Http\Controllers\Api\GiftCardController::class, 'apply']);
});

Route::prefix('vouchers')->group(function () {
    Route::post('/validate', [\App\Http\Controllers\Api\VoucherController::class, 'validateCode']);
    Route::post('/apply', [\App\Http\Controllers\Api\VoucherController::class, 'apply']);
    Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\VoucherController::class, 'index']);
        Route::post('/', [\App\Http\Controllers\Api\VoucherController::class, 'store']);
        Route::put('/{id}', [\App\Http\Controllers\Api\VoucherController::class, 'update']);
        Route::delete('/{id}', [\App\Http\Controllers\Api\VoucherController::class, 'destroy']);
    });
});

// ==================== OFFERS (MAKE AN OFFER) ====================
Route::prefix('offers')->middleware('auth:sanctum')->group(function () {
    Route::post('/', [\App\Http\Controllers\Api\OfferController::class, 'create']);
    Route::get('/sent', [\App\Http\Controllers\Api\OfferController::class, 'sentOffers']);
    Route::get('/received', [\App\Http\Controllers\Api\OfferController::class, 'receivedOffers']);
    Route::post('/{id}/counter', [\App\Http\Controllers\Api\OfferController::class, 'counter']);
    Route::post('/{id}/accept', [\App\Http\Controllers\Api\OfferController::class, 'accept']);
    Route::post('/{id}/reject', [\App\Http\Controllers\Api\OfferController::class, 'reject']);
});

// ==================== SUPPORT TICKETS ====================
Route::prefix('support')->middleware('auth:sanctum')->group(function () {
    Route::get('/tickets', [\App\Http\Controllers\Api\SupportController::class, 'index']);
    Route::post('/tickets', [\App\Http\Controllers\Api\SupportController::class, 'create']);
    Route::get('/tickets/{id}', [\App\Http\Controllers\Api\SupportController::class, 'show']);
    Route::post('/tickets/{id}/messages', [\App\Http\Controllers\Api\SupportController::class, 'addMessage']);
    Route::put('/tickets/{id}/close', [\App\Http\Controllers\Api\SupportController::class, 'close']);
    
    // Admin only
    Route::middleware('role:admin')->group(function () {
        Route::get('/tickets/all', [\App\Http\Controllers\Api\SupportController::class, 'allTickets']);
        Route::put('/tickets/{id}/assign', [\App\Http\Controllers\Api\SupportController::class, 'assign']);
        Route::put('/tickets/{id}/status', [\App\Http\Controllers\Api\SupportController::class, 'updateStatus']);
    });
});

// ==================== AFFILIATE PROGRAM ====================
Route::prefix('affiliates')->middleware('auth:sanctum')->group(function () {
    Route::post('/join', [\App\Http\Controllers\Api\AffiliateController::class, 'join']);
    Route::get('/dashboard', [\App\Http\Controllers\Api\AffiliateController::class, 'dashboard']);
    Route::get('/conversions', [\App\Http\Controllers\Api\AffiliateController::class, 'conversions']);
    Route::get('/stats', [\App\Http\Controllers\Api\AffiliateController::class, 'stats']);
});

// ==================== ADVANCED SEARCH ====================
Route::prefix('search')->group(function () {
    Route::get('/products', [\App\Http\Controllers\Api\SearchController::class, 'products']);
    Route::get('/autocomplete', [\App\Http\Controllers\Api\SearchController::class, 'autocomplete']);
    Route::get('/suggestions', [\App\Http\Controllers\Api\SearchController::class, 'suggestions']);
    Route::middleware('auth:sanctum')->get('/history', [\App\Http\Controllers\Api\SearchController::class, 'history']);
});

// ==================== RECENTLY VIEWED ====================
Route::prefix('recently-viewed')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [\App\Http\Controllers\Api\RecentlyViewedController::class, 'index']);
    Route::post('/', [\App\Http\Controllers\Api\RecentlyViewedController::class, 'track']);
    Route::delete('/{id}', [\App\Http\Controllers\Api\RecentlyViewedController::class, 'remove']);
});
