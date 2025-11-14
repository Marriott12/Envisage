
<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Debug test route for web middleware
Route::get('/web-test', function() {
    return response()->json(['message' => 'Web test route works']);
});

Route::get('/', function () {
    return response()->json([
        'name' => 'Envisage E-Commerce API',
        'version' => '1.0.0',
        'status' => 'online',
        'endpoints' => [
            'health_check' => url('/api/test'),
            'public_settings' => url('/api/settings/public'),
            'products' => url('/api/products'),
            'sitemap' => url('/api/sitemap.xml'),
            'robots' => url('/api/robots.txt'),
        ],
        'documentation' => 'See FEATURE_DOCUMENTATION.md for full API docs',
        'authentication' => [
            'register' => url('/api/register'),
            'login' => url('/api/login'),
        ]
    ], 200);
});
