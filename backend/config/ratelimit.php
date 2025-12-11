<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Rate Limiting Configuration
    |--------------------------------------------------------------------------
    |
    | Configure rate limits for different API endpoints
    |
    */

    'rate_limits' => [
        // Authentication endpoints
        'auth' => [
            'login' => '5,1',      // 5 attempts per minute
            'register' => '3,1',   // 3 attempts per minute
            'password_reset' => '3,10', // 3 attempts per 10 minutes
        ],

        // Public endpoints
        'public' => [
            'products' => '60,1',  // 60 requests per minute
            'search' => '30,1',    // 30 requests per minute
        ],

        // Authenticated endpoints
        'authenticated' => [
            'default' => '120,1',  // 120 requests per minute
            'upload' => '10,1',    // 10 uploads per minute
        ],

        // Admin endpoints
        'admin' => [
            'default' => '200,1',  // 200 requests per minute
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Throttle Response
    |--------------------------------------------------------------------------
    |
    | Custom response when rate limit is exceeded
    |
    */

    'throttle_message' => 'Too many requests. Please slow down.',

];
