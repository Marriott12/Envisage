<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Auth;
use Illuminate\Cache\RateLimiting\Limit;
use Symfony\Component\HttpFoundation\Response;

class AIRateLimiter
{
    /**
     * Handle an incoming request - Enterprise AI Rate Limiting
     * Different limits based on user tier (guest, customer, premium, admin)
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $service
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next, string $service): Response
    {
        $user = Auth::user();
        $tier = $this->getUserTier($user);
        
        // Get rate limit config for this service and tier
        $limits = config("ai.rate_limits.{$tier}.{$service}", '10,1');
        [$maxAttempts, $decayMinutes] = explode(',', $limits);
        
        // Create unique key for this user/IP and service
        $key = $this->resolveRequestSignature($request, $service);
        
        // Check rate limit
        $rateLimiter = RateLimiter::for($service, function (Request $request) use ($maxAttempts, $decayMinutes) {
            return Limit::perMinutes((int)$decayMinutes, (int)$maxAttempts);
        });
        
        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $retryAfter = RateLimiter::availableIn($key);
            
            return response()->json([
                'success' => false,
                'message' => 'Too many requests. Please try again later.',
                'error' => 'rate_limit_exceeded',
                'data' => [
                    'service' => $service,
                    'tier' => $tier,
                    'limit' => $maxAttempts . ' per ' . $decayMinutes . ' minute(s)',
                    'retry_after_seconds' => $retryAfter,
                ],
            ], 429, [
                'X-RateLimit-Limit' => $maxAttempts,
                'X-RateLimit-Remaining' => 0,
                'Retry-After' => $retryAfter,
                'X-RateLimit-Reset' => now()->addSeconds($retryAfter)->timestamp,
            ]);
        }
        
        // Hit the rate limiter
        RateLimiter::hit($key, $decayMinutes * 60);
        
        $remaining = $maxAttempts - RateLimiter::attempts($key);
        
        // Continue with request
        $response = $next($request);
        
        // Add rate limit headers
        $response->headers->set('X-RateLimit-Limit', $maxAttempts);
        $response->headers->set('X-RateLimit-Remaining', max(0, $remaining));
        $response->headers->set('X-RateLimit-Reset', now()->addSeconds($decayMinutes * 60)->timestamp);
        
        return $response;
    }
    
    /**
     * Determine user tier for rate limiting
     *
     * @param  \App\Models\User|null  $user
     * @return string
     */
    protected function getUserTier($user): string
    {
        if (!$user) {
            return 'guest';
        }
        
        if ($user->hasRole('admin')) {
            return 'admin';
        }
        
        if ($user->subscription_tier === 'premium') {
            return 'premium';
        }
        
        return 'customer';
    }
    
    /**
     * Resolve the request signature for rate limiting
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $service
     * @return string
     */
    protected function resolveRequestSignature(Request $request, string $service): string
    {
        $user = Auth::user();
        
        if ($user) {
            return sprintf('ai:ratelimit:%s:%s:%d',
                $service,
                $this->getUserTier($user),
                $user->id
            );
        }
        
        return sprintf('ai:ratelimit:%s:guest:%s',
            $service,
            $request->ip()
        );
    }
}
