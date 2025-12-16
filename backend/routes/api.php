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

// Product Reviews (Public)
Route::get('/products/{productId}/reviews', [\App\Http\Controllers\API\ReviewController::class, 'index']);
Route::get('/products/{productId}/reviews/statistics', [\App\Http\Controllers\API\ReviewController::class, 'statistics']);

// Wishlist Sharing (Public)
Route::get('/wishlists/shared/{token}', [\App\Http\Controllers\API\WishlistController::class, 'getShared']);

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
    
    // ========== PRODUCT REVIEWS ==========
    Route::get('/reviews/my-reviews', [\App\Http\Controllers\API\ReviewController::class, 'myReviews']);
    Route::post('/products/{productId}/reviews', [\App\Http\Controllers\API\ReviewController::class, 'store']);
    Route::put('/reviews/{reviewId}', [\App\Http\Controllers\API\ReviewController::class, 'update']);
    Route::delete('/reviews/{reviewId}', [\App\Http\Controllers\API\ReviewController::class, 'destroy']);
    Route::post('/reviews/{reviewId}/helpful', [\App\Http\Controllers\API\ReviewController::class, 'markHelpful']);
    Route::post('/reviews/{reviewId}/response', [\App\Http\Controllers\API\ReviewController::class, 'addResponse']);
    
    // ========== WISHLISTS ==========
    Route::get('/wishlists', [\App\Http\Controllers\API\WishlistController::class, 'index']);
    Route::post('/wishlists', [\App\Http\Controllers\API\WishlistController::class, 'store']);
    Route::get('/wishlists/{wishlistId}', [\App\Http\Controllers\API\WishlistController::class, 'show']);
    Route::put('/wishlists/{wishlistId}', [\App\Http\Controllers\API\WishlistController::class, 'update']);
    Route::delete('/wishlists/{wishlistId}', [\App\Http\Controllers\API\WishlistController::class, 'destroy']);
    Route::post('/wishlists/{wishlistId}/items', [\App\Http\Controllers\API\WishlistController::class, 'addItem']);
    Route::post('/wishlists/quick-add', [\App\Http\Controllers\API\WishlistController::class, 'quickAdd']);
    Route::delete('/wishlists/{wishlistId}/items/{itemId}', [\App\Http\Controllers\API\WishlistController::class, 'removeItem']);
    Route::put('/wishlists/{wishlistId}/items/{itemId}', [\App\Http\Controllers\API\WishlistController::class, 'updateItem']);
    Route::post('/wishlists/{wishlistId}/share', [\App\Http\Controllers\API\WishlistController::class, 'share']);
    Route::get('/products/{productId}/wishlist-check', [\App\Http\Controllers\API\WishlistController::class, 'checkProduct']);
    
    // ========== RECENTLY VIEWED ==========
    Route::get('/recently-viewed', [\App\Http\Controllers\API\RecentlyViewedController::class, 'index']);
    Route::post('/products/{productId}/track-view', [\App\Http\Controllers\API\RecentlyViewedController::class, 'track']);
    Route::delete('/recently-viewed', [\App\Http\Controllers\API\RecentlyViewedController::class, 'clear']);
    
    // ========== EXPRESS CHECKOUT ==========
    Route::get('/express-checkout/preferences', [\App\Http\Controllers\API\ExpressCheckoutController::class, 'getPreferences']);
    Route::put('/express-checkout/preferences', [\App\Http\Controllers\API\ExpressCheckoutController::class, 'updatePreferences']);
    Route::get('/express-checkout/payment-methods', [\App\Http\Controllers\API\ExpressCheckoutController::class, 'getPaymentMethods']);
    Route::post('/express-checkout/payment-methods', [\App\Http\Controllers\API\ExpressCheckoutController::class, 'addPaymentMethod']);
    Route::delete('/express-checkout/payment-methods/{methodId}', [\App\Http\Controllers\API\ExpressCheckoutController::class, 'deletePaymentMethod']);
    Route::post('/express-checkout/payment-methods/{methodId}/set-default', [\App\Http\Controllers\API\ExpressCheckoutController::class, 'setDefaultPaymentMethod']);
    Route::get('/express-checkout/addresses', [\App\Http\Controllers\API\ExpressCheckoutController::class, 'getAddresses']);
    Route::post('/express-checkout/addresses', [\App\Http\Controllers\API\ExpressCheckoutController::class, 'addAddress']);
    Route::put('/express-checkout/addresses/{addressId}', [\App\Http\Controllers\API\ExpressCheckoutController::class, 'updateAddress']);
    Route::delete('/express-checkout/addresses/{addressId}', [\App\Http\Controllers\API\ExpressCheckoutController::class, 'deleteAddress']);
    Route::post('/express-checkout', [\App\Http\Controllers\API\ExpressCheckoutController::class, 'expressCheckout']);
    
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

// ==================== TWO-FACTOR AUTHENTICATION ====================
Route::prefix('2fa')->middleware('auth:sanctum')->group(function () {
    Route::post('/enable', [\App\Http\Controllers\Api\TwoFactorController::class, 'enable']);
    Route::post('/verify', [\App\Http\Controllers\Api\TwoFactorController::class, 'verify']);
    Route::post('/disable', [\App\Http\Controllers\Api\TwoFactorController::class, 'disable']);
    Route::post('/regenerate-backup-codes', [\App\Http\Controllers\Api\TwoFactorController::class, 'regenerateBackupCodes']);
});

// 2FA validation endpoint (no auth required - used during login)
Route::post('/2fa/validate', [\App\Http\Controllers\Api\TwoFactorController::class, 'validateCode']);

// ==================== SECURITY ====================
Route::prefix('security')->middleware('auth:sanctum')->group(function () {
    Route::get('/logs', [\App\Http\Controllers\Api\SecurityController::class, 'logs']);
    Route::get('/login-activity', [\App\Http\Controllers\Api\SecurityController::class, 'loginActivity']);
    Route::get('/active-sessions', [\App\Http\Controllers\Api\SecurityController::class, 'activeSessions']);
    Route::get('/suspicious-activity', [\App\Http\Controllers\Api\SecurityController::class, 'suspiciousActivity']);
});

// ==================== GDPR COMPLIANCE ====================
Route::prefix('gdpr')->middleware('auth:sanctum')->group(function () {
    Route::post('/export-data', [\App\Http\Controllers\Api\GDPRController::class, 'exportData']);
    Route::get('/download-data/{filename}', [\App\Http\Controllers\Api\GDPRController::class, 'downloadData']);
    Route::get('/portability-export', [\App\Http\Controllers\Api\GDPRController::class, 'portabilityExport']);
    Route::post('/request-deletion', [\App\Http\Controllers\Api\GDPRController::class, 'requestDeletion']);
    Route::post('/cancel-deletion', [\App\Http\Controllers\Api\GDPRController::class, 'cancelDeletion']);
    Route::get('/consents', [\App\Http\Controllers\Api\GDPRController::class, 'getConsents']);
    Route::put('/consents', [\App\Http\Controllers\Api\GDPRController::class, 'updateConsents']);
});

// ==================== SHIPPING SYSTEM ====================
Route::prefix('shipping')->group(function () {
    // Public endpoints
    Route::post('/validate-address', [\App\Http\Controllers\Api\ShippingController::class, 'validateAddress']);
    Route::post('/calculate-cost', [\App\Http\Controllers\Api\ShippingController::class, 'calculateCost']);
    Route::post('/get-rates', [\App\Http\Controllers\Api\ShippingController::class, 'getRates']);
    Route::get('/methods', [\App\Http\Controllers\Api\ShippingController::class, 'getMethods']);
    
    // Authenticated endpoints
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/purchase-label', [\App\Http\Controllers\Api\ShippingController::class, 'purchaseLabel']);
        Route::get('/tracking/{orderId}', [\App\Http\Controllers\Api\ShippingController::class, 'getTracking']);
        Route::post('/return-label', [\App\Http\Controllers\Api\ShippingController::class, 'createReturnLabel']);
        Route::post('/batch-track', [\App\Http\Controllers\Api\ShippingController::class, 'batchTrack']);
    });
});

// ==================== BULK OPERATIONS ====================
Route::prefix('bulk')->middleware('auth:sanctum')->group(function () {
    Route::post('/products/update', [\App\Http\Controllers\Api\BulkOperationsController::class, 'bulkUpdateProducts']);
    Route::post('/products/delete', [\App\Http\Controllers\Api\BulkOperationsController::class, 'bulkDeleteProducts']);
    Route::get('/products/export', [\App\Http\Controllers\Api\BulkOperationsController::class, 'bulkExportProducts']);
    Route::post('/products/import', [\App\Http\Controllers\Api\BulkOperationsController::class, 'bulkImportProducts']);
    Route::post('/orders/update', [\App\Http\Controllers\Api\BulkOperationsController::class, 'bulkUpdateOrders']);
});

// ==================== LIVE CHAT ====================
Route::prefix('chat')->middleware('auth:sanctum')->group(function () {
    Route::get('/conversations', [\App\Http\Controllers\Api\LiveChatController::class, 'conversations']);
    Route::post('/conversations/start', [\App\Http\Controllers\Api\LiveChatController::class, 'startConversation']);
    Route::get('/conversations/{id}/messages', [\App\Http\Controllers\Api\LiveChatController::class, 'messages']);
    Route::post('/conversations/{id}/messages', [\App\Http\Controllers\Api\LiveChatController::class, 'sendMessage']);
    Route::get('/unread-count', [\App\Http\Controllers\Api\LiveChatController::class, 'unreadCount']);
    Route::put('/conversations/{id}/close', [\App\Http\Controllers\Api\LiveChatController::class, 'closeConversation']);
});

// ==================== WISHLIST SHARING ====================
Route::prefix('wishlist-share')->group(function () {
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/', [\App\Http\Controllers\Api\WishlistSharingController::class, 'share']);
        Route::delete('/{token}', [\App\Http\Controllers\Api\WishlistSharingController::class, 'revoke']);
    });
    Route::get('/{token}', [\App\Http\Controllers\Api\WishlistSharingController::class, 'getShared']);
});

// ==================== PRICE DROP ALERTS ====================
Route::prefix('price-alerts')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [\App\Http\Controllers\Api\PriceAlertController::class, 'index']);
    Route::post('/', [\App\Http\Controllers\Api\PriceAlertController::class, 'store']);
    Route::put('/{id}', [\App\Http\Controllers\Api\PriceAlertController::class, 'update']);
    Route::delete('/{id}', [\App\Http\Controllers\Api\PriceAlertController::class, 'destroy']);
});
Route::post('/price-alerts/check', [\App\Http\Controllers\Api\PriceAlertController::class, 'checkPrices']); // Cron job endpoint

// ==================== FLASH SALES ====================
Route::prefix('flash-sales')->group(function () {
    // Public endpoints
    Route::get('/active', [\App\Http\Controllers\Api\FlashSaleController::class, 'active']);
    Route::get('/upcoming', [\App\Http\Controllers\Api\FlashSaleController::class, 'upcoming']);
    Route::get('/{id}', [\App\Http\Controllers\Api\FlashSaleController::class, 'show']);
    Route::post('/{id}/subscribe', [\App\Http\Controllers\Api\FlashSaleController::class, 'subscribe']);
    
    // Admin endpoints
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/', [\App\Http\Controllers\Api\FlashSaleController::class, 'store']);
        Route::put('/{id}', [\App\Http\Controllers\Api\FlashSaleController::class, 'update']);
        Route::delete('/{id}', [\App\Http\Controllers\Api\FlashSaleController::class, 'destroy']);
    });
});

// ==================== BUNDLE DEALS ====================
Route::prefix('bundles')->group(function () {
    // Public endpoints
    Route::get('/', [\App\Http\Controllers\Api\BundleController::class, 'index']);
    Route::get('/{id}', [\App\Http\Controllers\Api\BundleController::class, 'show']);
    
    // Authenticated endpoints
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/', [\App\Http\Controllers\Api\BundleController::class, 'store']);
        Route::put('/{id}', [\App\Http\Controllers\Api\BundleController::class, 'update']);
        Route::delete('/{id}', [\App\Http\Controllers\Api\BundleController::class, 'destroy']);
        Route::post('/{id}/purchase', [\App\Http\Controllers\Api\BundleController::class, 'purchase']);
    });
});

// ==================== PRODUCT Q&A ====================
Route::prefix('products/{productId}/questions')->group(function () {
    // Public endpoints
    Route::get('/', [\App\Http\Controllers\Api\ProductQuestionController::class, 'index']);
    
    // Authenticated endpoints
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/', [\App\Http\Controllers\Api\ProductQuestionController::class, 'store']);
        Route::post('/{questionId}/answer', [\App\Http\Controllers\Api\ProductQuestionController::class, 'answer']);
        Route::post('/{questionId}/helpful', [\App\Http\Controllers\Api\ProductQuestionController::class, 'markHelpful']);
        Route::delete('/{questionId}', [\App\Http\Controllers\Api\ProductQuestionController::class, 'destroy']);
    });
});

// ==================== SUBSCRIPTIONS ====================
Route::prefix('subscriptions')->group(function () {
    // Public endpoints
    Route::get('/plans', [\App\Http\Controllers\Api\SubscriptionController::class, 'plans']);
    
    // Authenticated endpoints
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/current', [\App\Http\Controllers\Api\SubscriptionController::class, 'current']);
        Route::post('/subscribe', [\App\Http\Controllers\Api\SubscriptionController::class, 'subscribe']);
        Route::post('/cancel', [\App\Http\Controllers\Api\SubscriptionController::class, 'cancel']);
        Route::post('/resume', [\App\Http\Controllers\Api\SubscriptionController::class, 'resume']);
    });
    
    // Webhook endpoint (no auth - verified by Stripe signature)
    Route::post('/webhook', [\App\Http\Controllers\Api\SubscriptionController::class, 'webhook']);
});

// ==================== MARKETING AUTOMATION ====================
Route::prefix('marketing')->middleware('auth:sanctum')->group(function () {
    
    // Email Templates
    Route::prefix('templates')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\EmailTemplateController::class, 'index']);
        Route::get('/{id}', [\App\Http\Controllers\Api\EmailTemplateController::class, 'show']);
        Route::post('/', [\App\Http\Controllers\Api\EmailTemplateController::class, 'store']);
        Route::put('/{id}', [\App\Http\Controllers\Api\EmailTemplateController::class, 'update']);
        Route::delete('/{id}', [\App\Http\Controllers\Api\EmailTemplateController::class, 'destroy']);
        Route::post('/{id}/preview', [\App\Http\Controllers\Api\EmailTemplateController::class, 'preview']);
        Route::post('/{id}/duplicate', [\App\Http\Controllers\Api\EmailTemplateController::class, 'duplicate']);
    });
    
    // Campaigns
    Route::prefix('campaigns')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\CampaignController::class, 'index']);
        Route::get('/{id}', [\App\Http\Controllers\Api\CampaignController::class, 'show']);
        Route::post('/', [\App\Http\Controllers\Api\CampaignController::class, 'store']);
        Route::put('/{id}', [\App\Http\Controllers\Api\CampaignController::class, 'update']);
        Route::delete('/{id}', [\App\Http\Controllers\Api\CampaignController::class, 'destroy']);
        Route::post('/{id}/send', [\App\Http\Controllers\Api\CampaignController::class, 'send']);
        Route::get('/{id}/analytics', [\App\Http\Controllers\Api\CampaignController::class, 'analytics']);
    });
    
    // Campaign tracking (public - no auth)
    Route::get('/track/open/{logId}', [\App\Http\Controllers\Api\CampaignController::class, 'trackOpen'])->withoutMiddleware('auth:sanctum');
    Route::get('/track/click/{logId}', [\App\Http\Controllers\Api\CampaignController::class, 'trackClick'])->withoutMiddleware('auth:sanctum');
    
    // Automation Rules
    Route::prefix('automation')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\AutomationController::class, 'index']);
        Route::get('/stats', [\App\Http\Controllers\Api\AutomationController::class, 'stats']);
        Route::get('/{id}', [\App\Http\Controllers\Api\AutomationController::class, 'show']);
        Route::post('/', [\App\Http\Controllers\Api\AutomationController::class, 'store']);
        Route::put('/{id}', [\App\Http\Controllers\Api\AutomationController::class, 'update']);
        Route::delete('/{id}', [\App\Http\Controllers\Api\AutomationController::class, 'destroy']);
        Route::post('/{id}/toggle', [\App\Http\Controllers\Api\AutomationController::class, 'toggle']);
        Route::get('/{id}/executions', [\App\Http\Controllers\Api\AutomationController::class, 'executions']);
        Route::post('/{id}/trigger', [\App\Http\Controllers\Api\AutomationController::class, 'trigger']);
    });
});

// ==================== ANALYTICS & REPORTING ====================
Route::prefix('analytics')->middleware('auth:sanctum')->group(function () {
    
    // Event Tracking
    Route::post('/track', [\App\Http\Controllers\Api\AnalyticsController::class, 'trackEvent']);
    Route::get('/overview', [\App\Http\Controllers\Api\AnalyticsController::class, 'overview']);
    Route::get('/realtime', [\App\Http\Controllers\Api\AnalyticsController::class, 'realtime']);
    Route::get('/timeline', [\App\Http\Controllers\Api\AnalyticsController::class, 'timeline']);
    Route::get('/top-pages', [\App\Http\Controllers\Api\AnalyticsController::class, 'topPages']);
    Route::get('/traffic-sources', [\App\Http\Controllers\Api\AnalyticsController::class, 'trafficSources']);
    Route::get('/devices', [\App\Http\Controllers\Api\AnalyticsController::class, 'devices']);
    Route::get('/geography', [\App\Http\Controllers\Api\AnalyticsController::class, 'geography']);
    Route::get('/user-journey/{userId}', [\App\Http\Controllers\Api\AnalyticsController::class, 'userJourney']);
});

Route::prefix('dashboard')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [\App\Http\Controllers\Api\DashboardController::class, 'index']);
    Route::get('/stats', [\App\Http\Controllers\Api\DashboardController::class, 'stats']);
});

Route::prefix('reports')->middleware('auth:sanctum')->group(function () {
    Route::get('/sales', [\App\Http\Controllers\Api\ReportsController::class, 'sales']);
    Route::get('/products', [\App\Http\Controllers\Api\ReportsController::class, 'productPerformance']);
    Route::get('/customers', [\App\Http\Controllers\Api\ReportsController::class, 'customers']);
    Route::get('/cohort-analysis', [\App\Http\Controllers\Api\ReportsController::class, 'cohortAnalysis']);
    Route::get('/user-segments', [\App\Http\Controllers\Api\ReportsController::class, 'userSegments']);
    Route::post('/export', [\App\Http\Controllers\Api\ReportsController::class, 'export']);
});

// ==================== AI RECOMMENDATION ENGINE ====================
Route::prefix('recommendations')->group(function () {
    
    // Personalized Recommendations (requires auth for personalized, fallback to cold start)
    Route::get('/for-you', [\App\Http\Controllers\RecommendationController::class, 'getPersonalized']);
    
    // Trending Products (public)
    Route::get('/trending', [\App\Http\Controllers\RecommendationController::class, 'getTrending']);
    Route::get('/trending/category/{categoryId}', [\App\Http\Controllers\RecommendationController::class, 'getTrendingByCategory']);
    Route::get('/emerging-trends', [\App\Http\Controllers\RecommendationController::class, 'getEmergingTrends']);
    
    // Product-based Recommendations (public)
    Route::get('/similar/{productId}', [\App\Http\Controllers\RecommendationController::class, 'getSimilar']);
    Route::get('/frequently-bought/{productId}', [\App\Http\Controllers\RecommendationController::class, 'getFrequentlyBoughtTogether']);
    
    // Search-based Recommendations (requires auth)
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/search-based', [\App\Http\Controllers\RecommendationController::class, 'getSearchRecommendations']);
        Route::post('/track-interaction', [\App\Http\Controllers\RecommendationController::class, 'trackInteraction']);
    });
    
    // Search Tracking (public for tracking, auth for history)
    Route::post('/track-search', [\App\Http\Controllers\RecommendationController::class, 'trackSearch']);
    Route::get('/popular-searches', [\App\Http\Controllers\RecommendationController::class, 'getPopularSearches']);
    
    // Performance Metrics (admin only)
    Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
        Route::get('/performance', [\App\Http\Controllers\RecommendationController::class, 'getPerformance']);
        Route::get('/failed-searches', [\App\Http\Controllers\RecommendationController::class, 'getFailedSearches']);
    });
});

// ==================== REFERRAL PROGRAM ====================
Route::prefix('referrals')->group(function () {
    
    // Public endpoints
    Route::get('/tiers', [\App\Http\Controllers\ReferralController::class, 'getTiers']);
    Route::get('/leaderboard', [\App\Http\Controllers\ReferralController::class, 'getLeaderboard']);
    Route::get('/validate/{code}', [\App\Http\Controllers\ReferralController::class, 'validateCode']);
    Route::get('/track-click/{token}', [\App\Http\Controllers\ReferralController::class, 'trackClick']);
    
    // Authenticated endpoints
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\ReferralController::class, 'getDashboard']);
        Route::get('/links', [\App\Http\Controllers\ReferralController::class, 'getLinks']);
        Route::post('/links', [\App\Http\Controllers\ReferralController::class, 'generateLink']);
        Route::patch('/links/{id}', [\App\Http\Controllers\ReferralController::class, 'updateLinkStatus']);
        Route::post('/invite', [\App\Http\Controllers\ReferralController::class, 'sendInvitation']);
        Route::get('/my-referrals', [\App\Http\Controllers\ReferralController::class, 'getReferrals']);
        Route::get('/rewards', [\App\Http\Controllers\ReferralController::class, 'getRewards']);
    });
    
    // Admin endpoints
    Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
        Route::get('/analytics', [\App\Http\Controllers\ReferralController::class, 'getAnalytics']);
    });
});

// ========== DYNAMIC PRICING ROUTES (Phase 5) ==========
Route::prefix('pricing')->group(function () {
    
    // Public endpoints
    Route::get('/surge/{productId}', [\App\Http\Controllers\DynamicPricingController::class, 'getSurgePricing']);
    
    // Authenticated endpoints
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/recommend/{productId}', [\App\Http\Controllers\DynamicPricingController::class, 'getPriceRecommendation']);
        Route::get('/history/{productId}', [\App\Http\Controllers\DynamicPricingController::class, 'getPriceHistory']);
        Route::get('/competitors/{productId}', [\App\Http\Controllers\DynamicPricingController::class, 'getCompetitorPrices']);
        Route::get('/forecast/{productId}', [\App\Http\Controllers\DynamicPricingController::class, 'getDemandForecast']);
        Route::get('/check-surge/{productId}', [\App\Http\Controllers\DynamicPricingController::class, 'checkSurgeConditions']);
    });
    
    // Admin endpoints
    Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
        // Price management
        Route::post('/apply', [\App\Http\Controllers\DynamicPricingController::class, 'applyPriceChange']);
        Route::post('/bulk-optimize', [\App\Http\Controllers\DynamicPricingController::class, 'bulkOptimize']);
        
        // Rule management
        Route::get('/rules', [\App\Http\Controllers\DynamicPricingController::class, 'getRules']);
        Route::post('/rules', [\App\Http\Controllers\DynamicPricingController::class, 'createRule']);
        Route::put('/rules/{id}', [\App\Http\Controllers\DynamicPricingController::class, 'updateRule']);
        Route::delete('/rules/{id}', [\App\Http\Controllers\DynamicPricingController::class, 'deleteRule']);
        
        // Experiment management
        Route::get('/experiments', [\App\Http\Controllers\DynamicPricingController::class, 'listExperiments']);
        Route::post('/experiments', [\App\Http\Controllers\DynamicPricingController::class, 'startExperiment']);
        Route::get('/experiments/{id}', [\App\Http\Controllers\DynamicPricingController::class, 'getExperimentResults']);
        Route::post('/experiments/{id}/complete', [\App\Http\Controllers\DynamicPricingController::class, 'completeExperiment']);
        
        // Surge pricing management
        Route::post('/surge', [\App\Http\Controllers\DynamicPricingController::class, 'activateSurgePricing']);
        Route::delete('/surge/{productId}', [\App\Http\Controllers\DynamicPricingController::class, 'deactivateSurgePricing']);
        
        // Analytics
        Route::get('/analytics', [\App\Http\Controllers\DynamicPricingController::class, 'getAnalytics']);
    });
});

// ==================== FRAUD DETECTION ROUTES ====================
Route::prefix('fraud')->group(function () {
    // Public check
    Route::post('/blacklist/check', [\App\Http\Controllers\Api\FraudDetectionController::class, 'checkBlacklist']);
    
    // Authenticated routes
    Route::middleware('auth:sanctum')->group(function () {
        // Get fraud score
        Route::get('/score/{orderId}', [\App\Http\Controllers\Api\FraudDetectionController::class, 'getScore']);
        
        // Velocity stats
        Route::get('/velocity/{identifier}', [\App\Http\Controllers\Api\FraudDetectionController::class, 'getVelocityStats']);
    });
    
    // Admin-only routes
    Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
        // Analysis
        Route::post('/analyze/{orderId}', [\App\Http\Controllers\Api\FraudDetectionController::class, 'analyzeOrder']);
        Route::post('/reanalyze/{orderId}', [\App\Http\Controllers\Api\FraudDetectionController::class, 'reanalyze']);
        Route::post('/bulk-analyze', [\App\Http\Controllers\Api\FraudDetectionController::class, 'bulkAnalyze']);
        
        // Review management
        Route::get('/pending-reviews', [\App\Http\Controllers\Api\FraudDetectionController::class, 'getPendingReviews']);
        Route::post('/approve/{scoreId}', [\App\Http\Controllers\Api\FraudDetectionController::class, 'approve']);
        Route::post('/reject/{scoreId}', [\App\Http\Controllers\Api\FraudDetectionController::class, 'reject']);
        Route::post('/false-positive/{scoreId}', [\App\Http\Controllers\Api\FraudDetectionController::class, 'markFalsePositive']);
        
        // Rule management
        Route::get('/rules', [\App\Http\Controllers\Api\FraudDetectionController::class, 'listRules']);
        Route::post('/rules', [\App\Http\Controllers\Api\FraudDetectionController::class, 'createRule']);
        Route::put('/rules/{id}', [\App\Http\Controllers\Api\FraudDetectionController::class, 'updateRule']);
        Route::delete('/rules/{id}', [\App\Http\Controllers\Api\FraudDetectionController::class, 'deleteRule']);
        
        // Blacklist management
        Route::get('/blacklist', [\App\Http\Controllers\Api\FraudDetectionController::class, 'getBlacklist']);
        Route::post('/blacklist', [\App\Http\Controllers\Api\FraudDetectionController::class, 'addToBlacklist']);
        Route::delete('/blacklist/{id}', [\App\Http\Controllers\Api\FraudDetectionController::class, 'removeFromBlacklist']);
        
        // Fraud attempts
        Route::get('/attempts', [\App\Http\Controllers\Api\FraudDetectionController::class, 'getAttempts']);
        
        // Analytics
        Route::get('/analytics', [\App\Http\Controllers\Api\FraudDetectionController::class, 'getAnalytics']);
    });
});

// ==================== CUSTOMER SEGMENTATION ROUTES ====================
Route::prefix('segmentation')->middleware(['auth:sanctum', 'role:admin'])->group(function () {
    // Customer profile
    Route::get('/customer/{userId}', [\App\Http\Controllers\Api\SegmentationController::class, 'getCustomerProfile']);
    Route::post('/analyze/{userId}', [\App\Http\Controllers\Api\SegmentationController::class, 'analyzeUser']);
    
    // Analytics
    Route::get('/analytics', [\App\Http\Controllers\Api\SegmentationController::class, 'getAnalytics']);
    
    // RFM Analysis
    Route::get('/rfm', [\App\Http\Controllers\Api\SegmentationController::class, 'getRfmScores']);
    Route::get('/rfm/distribution', [\App\Http\Controllers\Api\SegmentationController::class, 'getRfmDistribution']);
    Route::post('/rfm/calculate/{userId}', [\App\Http\Controllers\Api\SegmentationController::class, 'calculateRfm']);
    
    // Churn Prediction
    Route::get('/churn', [\App\Http\Controllers\Api\SegmentationController::class, 'getChurnPredictions']);
    Route::post('/churn/predict/{userId}', [\App\Http\Controllers\Api\SegmentationController::class, 'predictChurn']);
    Route::post('/churn/trigger-interventions', [\App\Http\Controllers\Api\SegmentationController::class, 'triggerChurnInterventions']);
    
    // Customer Lifetime Value
    Route::get('/clv', [\App\Http\Controllers\Api\SegmentationController::class, 'getClvData']);
    Route::get('/clv/statistics', [\App\Http\Controllers\Api\SegmentationController::class, 'getClvStatistics']);
    Route::post('/clv/calculate/{userId}', [\App\Http\Controllers\Api\SegmentationController::class, 'calculateClv']);
    
    // Next Purchase Prediction
    Route::get('/next-purchase', [\App\Http\Controllers\Api\SegmentationController::class, 'getNextPurchasePredictions']);
    Route::post('/next-purchase/predict/{userId}', [\App\Http\Controllers\Api\SegmentationController::class, 'predictNextPurchase']);
    Route::post('/next-purchase/send-notifications', [\App\Http\Controllers\Api\SegmentationController::class, 'sendNextPurchaseNotifications']);
    
    // Segments
    Route::get('/segments', [\App\Http\Controllers\Api\SegmentationController::class, 'listSegments']);
    Route::post('/segments', [\App\Http\Controllers\Api\SegmentationController::class, 'createSegment']);
    Route::post('/segments/{id}/recalculate', [\App\Http\Controllers\Api\SegmentationController::class, 'recalculateSegment']);
    Route::post('/segments/initialize-defaults', [\App\Http\Controllers\Api\SegmentationController::class, 'initializeDefaultSegments']);
    
    // Complete segmentation
    Route::post('/run-complete', [\App\Http\Controllers\Api\SegmentationController::class, 'runCompleteSegmentation']);
});

// ==================== INVENTORY FORECASTING & AUTO-REORDERING ROUTES ====================
Route::prefix('inventory')->middleware(['auth:sanctum', 'role:admin'])->group(function () {
    // Analytics & Health
    Route::get('/analytics', [\App\Http\Controllers\InventoryController::class, 'getAnalytics']);
    Route::get('/health-score', [\App\Http\Controllers\InventoryController::class, 'getHealthScore']);
    
    // Forecasting
    Route::post('/forecasts/generate/{productId}', [\App\Http\Controllers\InventoryController::class, 'generateForecast']);
    Route::get('/forecasts/{productId}', [\App\Http\Controllers\InventoryController::class, 'getForecasts']);
    Route::get('/forecasts/accuracy-report', [\App\Http\Controllers\InventoryController::class, 'getForecastAccuracy']);
    Route::post('/forecasts/generate-all', [\App\Http\Controllers\InventoryController::class, 'generateAllForecasts']);
    
    // Reorder Points
    Route::get('/reorder-points', [\App\Http\Controllers\InventoryController::class, 'listReorderPoints']);
    Route::get('/reorder-points/{id}', [\App\Http\Controllers\InventoryController::class, 'getReorderPoint']);
    Route::post('/reorder-points', [\App\Http\Controllers\InventoryController::class, 'createReorderPoint']);
    Route::post('/reorder-points/{id}/update-from-history', [\App\Http\Controllers\InventoryController::class, 'updateReorderPointFromHistory']);
    Route::post('/reorder-points/{id}/update-from-forecast', [\App\Http\Controllers\InventoryController::class, 'updateReorderPointFromForecast']);
    Route::post('/reorder-points/update-all', [\App\Http\Controllers\InventoryController::class, 'updateAllReorderPoints']);
    Route::post('/reorder-points/check-needs', [\App\Http\Controllers\InventoryController::class, 'checkReorderNeeds']);
    
    // Stockout Prediction
    Route::get('/stockout-risk/{productId}', [\App\Http\Controllers\InventoryController::class, 'getStockoutRisk']);
    Route::get('/stockout-risks', [\App\Http\Controllers\InventoryController::class, 'getStockoutRisks']);
    
    // Purchase Orders
    Route::get('/purchase-orders', [\App\Http\Controllers\InventoryController::class, 'listPurchaseOrders']);
    Route::get('/purchase-orders/{id}', [\App\Http\Controllers\InventoryController::class, 'getPurchaseOrder']);
    Route::post('/purchase-orders', [\App\Http\Controllers\InventoryController::class, 'createPurchaseOrder']);
    Route::put('/purchase-orders/{id}/status', [\App\Http\Controllers\InventoryController::class, 'updatePurchaseOrderStatus']);
    Route::post('/purchase-orders/{id}/receive', [\App\Http\Controllers\InventoryController::class, 'receivePurchaseOrder']);
    Route::post('/purchase-orders/{id}/mark-received', [\App\Http\Controllers\InventoryController::class, 'markPurchaseOrderReceived']);
    
    // Suppliers
    Route::get('/suppliers', [\App\Http\Controllers\InventoryController::class, 'listSuppliers']);
    Route::get('/suppliers/{id}', [\App\Http\Controllers\InventoryController::class, 'getSupplier']);
    Route::post('/suppliers', [\App\Http\Controllers\InventoryController::class, 'createSupplier']);
    Route::put('/suppliers/{id}', [\App\Http\Controllers\InventoryController::class, 'updateSupplier']);
    Route::get('/suppliers/recommend/{productId}', [\App\Http\Controllers\InventoryController::class, 'recommendSupplier']);
    
    // Supplier Performance
    Route::get('/supplier-performance', [\App\Http\Controllers\InventoryController::class, 'listSupplierPerformance']);
    Route::post('/supplier-performance/evaluate/{supplierId}', [\App\Http\Controllers\InventoryController::class, 'evaluateSupplier']);
    Route::post('/supplier-performance/evaluate-all', [\App\Http\Controllers\InventoryController::class, 'evaluateAllSuppliers']);
    
    // Stock Alerts
    Route::get('/alerts', [\App\Http\Controllers\InventoryController::class, 'listStockAlerts']);
    Route::post('/alerts/{id}/resolve', [\App\Http\Controllers\InventoryController::class, 'resolveAlert']);
    Route::post('/alerts/generate', [\App\Http\Controllers\InventoryController::class, 'generateAlerts']);
    Route::post('/alerts/auto-resolve', [\App\Http\Controllers\InventoryController::class, 'autoResolveAlerts']);
    
    // Complete Optimization
    Route::post('/optimize', [\App\Http\Controllers\InventoryController::class, 'runOptimization']);
});

// ==================== ADVANCED SEARCH (ELASTICSEARCH) ROUTES ====================
// Public search routes
Route::get('/search', [\App\Http\Controllers\SearchController::class, 'search']);
Route::get('/search/autocomplete', [\App\Http\Controllers\SearchController::class, 'autocomplete']);
Route::post('/search/log-click', [\App\Http\Controllers\SearchController::class, 'logClick']);
Route::get('/search/popular', [\App\Http\Controllers\SearchController::class, 'getPopularSearches']);

// Admin search management routes
Route::prefix('search')->middleware(['auth:sanctum', 'role:admin'])->group(function () {
    // Analytics
    Route::get('/analytics', [\App\Http\Controllers\SearchController::class, 'getAnalytics']);
    Route::get('/no-results', [\App\Http\Controllers\SearchController::class, 'getNoResultSearches']);
    
    // Synonym Management
    Route::get('/synonyms', [\App\Http\Controllers\SearchController::class, 'listSynonyms']);
    Route::post('/synonyms', [\App\Http\Controllers\SearchController::class, 'createSynonym']);
    Route::put('/synonyms/{id}', [\App\Http\Controllers\SearchController::class, 'updateSynonym']);
    Route::delete('/synonyms/{id}', [\App\Http\Controllers\SearchController::class, 'deleteSynonym']);
    Route::post('/synonyms/initialize', [\App\Http\Controllers\SearchController::class, 'initializeSynonyms']);
    Route::get('/synonyms/statistics', [\App\Http\Controllers\SearchController::class, 'getSynonymStatistics']);
    
    // Elasticsearch Index Management
    Route::post('/index/product/{productId}', [\App\Http\Controllers\SearchController::class, 'indexProduct']);
    Route::post('/index/bulk', [\App\Http\Controllers\SearchController::class, 'bulkIndexProducts']);
    Route::delete('/index/product/{productId}', [\App\Http\Controllers\SearchController::class, 'deleteProductFromIndex']);
});

// ==================== SOCIAL COMMERCE INTEGRATION ROUTES ====================
Route::prefix('social-commerce')->middleware(['auth:sanctum', 'role:admin'])->group(function () {
    // Product Sync
    Route::get('/products', [\App\Http\Controllers\SocialCommerceController::class, 'listProducts']);
    Route::get('/products/{id}', [\App\Http\Controllers\SocialCommerceController::class, 'getProduct']);
    Route::post('/products/sync', [\App\Http\Controllers\SocialCommerceController::class, 'syncProduct']);
    Route::post('/products/bulk-sync', [\App\Http\Controllers\SocialCommerceController::class, 'bulkSyncProducts']);
    Route::post('/products/{productId}/update-inventory', [\App\Http\Controllers\SocialCommerceController::class, 'updateInventory']);
    Route::delete('/products/{productId}/platform/{platform}', [\App\Http\Controllers\SocialCommerceController::class, 'removeProduct']);
    Route::get('/products/statistics', [\App\Http\Controllers\SocialCommerceController::class, 'getProductStatistics']);
    
    // Order Management
    Route::get('/orders', [\App\Http\Controllers\SocialCommerceController::class, 'listOrders']);
    Route::get('/orders/{id}', [\App\Http\Controllers\SocialCommerceController::class, 'getOrder']);
    Route::post('/orders/import', [\App\Http\Controllers\SocialCommerceController::class, 'importOrders']);
    Route::post('/orders/{id}/complete', [\App\Http\Controllers\SocialCommerceController::class, 'completeOrder']);
    Route::get('/orders/statistics', [\App\Http\Controllers\SocialCommerceController::class, 'getOrderStatistics']);
    
    // Sync Logs
    Route::get('/sync-logs', [\App\Http\Controllers\SocialCommerceController::class, 'listSyncLogs']);
    Route::get('/sync-logs/{id}', [\App\Http\Controllers\SocialCommerceController::class, 'getSyncLog']);
    Route::get('/sync-logs/statistics', [\App\Http\Controllers\SocialCommerceController::class, 'getSyncStatistics']);
    Route::get('/sync-logs/is-due', [\App\Http\Controllers\SocialCommerceController::class, 'isSyncDue']);
    
    // Analytics
    Route::get('/analytics', [\App\Http\Controllers\SocialCommerceController::class, 'getAnalytics']);
    Route::get('/analytics/platform-comparison', [\App\Http\Controllers\SocialCommerceController::class, 'getPlatformComparison']);
    
    // ========== PHASE 2 FEATURES ==========
    
    // Stock Alerts
    Route::post('/products/{productId}/stock-alert', [\App\Http\Controllers\API\StockAlertController::class, 'subscribe']);
    Route::delete('/stock-alerts/{id}', [\App\Http\Controllers\API\StockAlertController::class, 'unsubscribe']);
    Route::get('/stock-alerts', [\App\Http\Controllers\API\StockAlertController::class, 'myAlerts']);
    Route::get('/products/{productId}/stock-alert/check', [\App\Http\Controllers\API\StockAlertController::class, 'checkAlert']);
    
    // Product Bundles
    Route::get('/bundles', [\App\Http\Controllers\API\BundleController::class, 'index']);
    Route::get('/bundles/{id}', [\App\Http\Controllers\API\BundleController::class, 'show']);
    Route::get('/products/{productId}/frequently-bought-together', [\App\Http\Controllers\API\BundleController::class, 'frequentlyBoughtTogether']);
    Route::post('/bundles', [\App\Http\Controllers\API\BundleController::class, 'store'])->middleware('role:seller,admin');
    Route::delete('/bundles/{id}', [\App\Http\Controllers\API\BundleController::class, 'destroy'])->middleware('role:seller,admin');
    
    // Flash Sales
    Route::get('/flash-sales/active', [\App\Http\Controllers\API\FlashSaleController::class, 'active']);
    Route::get('/flash-sales/{id}', [\App\Http\Controllers\API\FlashSaleController::class, 'show']);
    Route::get('/flash-sales/{flashSaleId}/products/{productId}/eligibility', [\App\Http\Controllers\API\FlashSaleController::class, 'checkEligibility']);
    Route::get('/daily-deal', [\App\Http\Controllers\API\FlashSaleController::class, 'dailyDeal']);
    
    // Gift Cards
    Route::get('/gift-cards/templates', [\App\Http\Controllers\API\GiftCardController::class, 'templates']);
    Route::post('/gift-cards/purchase', [\App\Http\Controllers\API\GiftCardController::class, 'purchase']);
    Route::post('/gift-cards/check-balance', [\App\Http\Controllers\API\GiftCardController::class, 'checkBalance']);
    Route::get('/gift-cards/my-cards', [\App\Http\Controllers\API\GiftCardController::class, 'myGiftCards']);
    Route::post('/gift-cards/redeem', [\App\Http\Controllers\API\GiftCardController::class, 'redeem']);
    
    // Auctions
    Route::get('/auctions', [\App\Http\Controllers\API\AuctionController::class, 'index']);
    Route::get('/auctions/{id}', [\App\Http\Controllers\API\AuctionController::class, 'show']);
    Route::post('/auctions/{id}/bid', [\App\Http\Controllers\API\AuctionController::class, 'placeBid']);
    Route::post('/auctions/{id}/auto-bid', [\App\Http\Controllers\API\AuctionController::class, 'setAutoBid']);
    Route::post('/auctions/{id}/watch', [\App\Http\Controllers\API\AuctionController::class, 'watch']);
    Route::delete('/auctions/{id}/watch', [\App\Http\Controllers\API\AuctionController::class, 'unwatch']);
    Route::get('/auctions/my-bids', [\App\Http\Controllers\API\AuctionController::class, 'myBids']);
});
