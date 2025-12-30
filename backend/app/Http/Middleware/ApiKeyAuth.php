<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ApiKeyAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $apiKey = $request->header('X-API-Key');

        if (!$apiKey) {
            return response()->json([
                'error' => 'API key is required',
                'message' => 'Please provide a valid API key in the X-API-Key header'
            ], 401);
        }

        // Check if API key is valid (you should store these in database)
        $validApiKeys = config('api.keys', []);

        if (!in_array($apiKey, $validApiKeys)) {
            // Log invalid API key attempt
            \Log::warning('Invalid API key attempted', [
                'api_key' => substr($apiKey, 0, 8) . '***',
                'ip' => $request->ip(),
                'endpoint' => $request->path()
            ]);

            return response()->json([
                'error' => 'Invalid API key',
                'message' => 'The provided API key is not valid'
            ], 403);
        }

        // Rate limiting per API key
        $cacheKey = 'api_key_rate_limit:' . md5($apiKey);
        $requests = Cache::get($cacheKey, 0);

        $limit = config('api.rate_limit', 100); // 100 requests per minute
        
        if ($requests >= $limit) {
            return response()->json([
                'error' => 'Rate limit exceeded',
                'message' => 'Too many requests. Please try again later.',
                'retry_after' => 60
            ], 429);
        }

        Cache::put($cacheKey, $requests + 1, now()->addMinute());

        return $next($request);
    }
}
