<?php

return [

    /*
    |--------------------------------------------------------------------------
    | API Configuration
    |--------------------------------------------------------------------------
    */

    'version' => '2.0.0',

    /*
    |--------------------------------------------------------------------------
    | API Keys
    |--------------------------------------------------------------------------
    | Store your API keys securely. In production, these should be in .env
    */

    'keys' => [
        env('API_KEY_1'),
        env('API_KEY_2'),
        env('API_KEY_3'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    */

    'rate_limit' => env('API_RATE_LIMIT', 100), // per minute

    /*
    |--------------------------------------------------------------------------
    | Swagger Documentation
    |--------------------------------------------------------------------------
    */

    'swagger' => [
        'title' => 'Envisage AI Platform API',
        'description' => 'Complete AI-powered marketplace API',
        'version' => '2.0.0',
        'schemes' => ['http', 'https'],
        'host' => env('APP_URL', 'http://localhost:8000'),
    ],

    /*
    |--------------------------------------------------------------------------
    | CORS Settings
    |--------------------------------------------------------------------------
    */

    'cors' => [
        'allowed_origins' => [
            'http://localhost:3000',
            'https://envisage.com',
        ],
        'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
        'allowed_headers' => ['Content-Type', 'Authorization', 'X-API-Key'],
        'exposed_headers' => ['X-Total-Count', 'X-Page-Count'],
        'max_age' => 86400,
    ],

    /*
    |--------------------------------------------------------------------------
    | Response Formatting
    |--------------------------------------------------------------------------
    */

    'response' => [
        'include_metadata' => true,
        'include_links' => true,
        'pretty_print' => env('APP_DEBUG', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Pagination
    |--------------------------------------------------------------------------
    */

    'pagination' => [
        'default_per_page' => 15,
        'max_per_page' => 100,
    ],

];
