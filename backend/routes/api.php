<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\BlogPostController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\RoleController;

// Debug test route
Route::get('/test', function() {
    return response()->json(['message' => 'API test route works']);
});

// Authentication routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);

// Role and Permission management routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/users/{userId}/assign-role', [RoleController::class, 'assignRole']);
    Route::post('/users/{userId}/remove-role', [RoleController::class, 'removeRole']);
    Route::post('/users/{userId}/give-permission', [RoleController::class, 'givePermission']);
    Route::post('/users/{userId}/revoke-permission', [RoleController::class, 'revokePermission']);

    // Protect create, update, delete for products
    Route::post('/products', [ProductController::class, 'store']);
    Route::put('/products/{id}', [ProductController::class, 'update']);
    Route::delete('/products/{id}', [ProductController::class, 'destroy']);

    // Protect create, update, delete for orders
    Route::post('/orders', [OrderController::class, 'store']);
    Route::put('/orders/{id}', [OrderController::class, 'update']);
    Route::delete('/orders/{id}', [OrderController::class, 'destroy']);

    // Protect create, update, delete for blog posts
    Route::post('/blog-posts', [BlogPostController::class, 'store']);
    Route::put('/blog-posts/{id}', [BlogPostController::class, 'update']);
    Route::delete('/blog-posts/{id}', [BlogPostController::class, 'destroy']);

    // Protect cart actions
    Route::post('/cart', [CartController::class, 'store']);
    Route::post('/cart/{cartId}/add-item', [CartController::class, 'addItem']);
    Route::delete('/cart/{cartId}/remove-item/{itemId}', [CartController::class, 'removeItem']);
    Route::delete('/cart/{cartId}/clear', [CartController::class, 'clear']);

    // Protect payments
    Route::post('/payments', [PaymentController::class, 'store']);
    Route::put('/payments/{id}', [PaymentController::class, 'update']);
    Route::delete('/payments/{id}', [PaymentController::class, 'destroy']);
});

// Public GET routes
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{id}', [ProductController::class, 'show']);
Route::get('/orders', [OrderController::class, 'index']);
Route::get('/orders/{id}', [OrderController::class, 'show']);
Route::get('/blog-posts', [BlogPostController::class, 'index']);
Route::get('/blog-posts/{id}', [BlogPostController::class, 'show']);
Route::get('/cart/{userId}', [CartController::class, 'index']);
Route::get('/payments', [PaymentController::class, 'index']);
Route::get('/payments/{id}', [PaymentController::class, 'show']);

// Authenticated user info
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Seller routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/seller/listings', [\App\Http\Controllers\SellerController::class, 'getListings']);
    Route::get('/seller/analytics', [\App\Http\Controllers\SellerController::class, 'getAnalytics']);
});

// Review routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/products/{productId}/reviews', [\App\Http\Controllers\ReviewController::class, 'store']);
    Route::get('/products/{productId}/reviews', [\App\Http\Controllers\ReviewController::class, 'index']);
});

// Notification routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/notifications', [\App\Http\Controllers\NotificationController::class, 'index']);
    Route::post('/notifications/{id}/read', [\App\Http\Controllers\NotificationController::class, 'markAsRead']);
});

// Admin routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/admin/overview', [\App\Http\Controllers\AdminController::class, 'getOverview']);
    Route::get('/admin/users', [\App\Http\Controllers\AdminController::class, 'getUsers']);
    Route::put('/admin/users/{id}', [\App\Http\Controllers\AdminController::class, 'updateUser']);
});

// Profile routes
Route::middleware('auth:sanctum')->group(function () {
    Route::put('/user', function (Request $request) {
        $user = $request->user();
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
        ]);
        $user->update($validated);
        return response()->json($user);
    });
});

// Cart item routes
Route::middleware('auth:sanctum')->group(function () {
    Route::put('/cart/{cartId}/item/{itemId}', [\App\Http\Controllers\CartController::class, 'updateItem']);
    Route::delete('/cart/{cartId}/item/{itemId}', [\App\Http\Controllers\CartController::class, 'removeItem']);
});
