<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AdminDashboardController;

// Admin routes - require authentication and admin role
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {
    
    // Dashboard
    Route::get('/dashboard', [AdminDashboardController::class, 'index']);
    Route::get('/analytics', [AdminDashboardController::class, 'analytics']);
    
    // AI Metrics
    Route::get('/ai-metrics', [AdminDashboardController::class, 'aiMetrics']);
    
    // Queue Monitoring
    Route::get('/queue/monitor', [AdminDashboardController::class, 'queueMonitor']);
    Route::post('/queue/retry', [AdminDashboardController::class, 'retryJob']);
    
    // User Management
    Route::get('/users', [AdminDashboardController::class, 'users']);
    Route::patch('/users/{userId}/status', [AdminDashboardController::class, 'updateUserStatus']);
    
    // System Configuration
    Route::get('/configuration', [AdminDashboardController::class, 'configuration']);
    
});
