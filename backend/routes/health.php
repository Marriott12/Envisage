<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HealthCheckController;

// Health Check Routes
Route::get('/health', [HealthCheckController::class, 'index']);
Route::get('/health/detailed', [HealthCheckController::class, 'detailed']);
Route::get('/health/metrics', [HealthCheckController::class, 'metrics']);
Route::get('/health/websocket', [HealthCheckController::class, 'websocket']);

// API Documentation
Route::get('/documentation', function () {
    return view('swagger');
});

Route::get('/api-docs', function () {
    return response()->file(storage_path('api-docs/api-docs.json'));
});
