<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AICacheService
{
    /**
     * Get cached AI response or execute callback
     *
     * @param string $service AI service name
     * @param string $cacheKey Unique cache key
     * @param callable $callback Function to execute if cache miss
     * @param int|null $ttl Cache duration in seconds (null = use config default)
     * @return mixed
     */
    public function remember(string $service, string $cacheKey, callable $callback, ?int $ttl = null)
    {
        if (!config('ai.enabled')) {
            return $callback();
        }
        
        $ttl = $ttl ?? config("ai.{$service}.cache_ttl", 300);
        
        // Skip caching if TTL is 0
        if ($ttl === 0) {
            return $callback();
        }
        
        $fullKey = $this->generateCacheKey($service, $cacheKey);
        
        return Cache::remember($fullKey, $ttl, function() use ($callback, $service, $cacheKey) {
            Log::info("AI Cache Miss: {$service}/{$cacheKey}");
            return $callback();
        });
    }
    
    /**
     * Clear cached AI response
     *
     * @param string $service
     * @param string $cacheKey
     * @return bool
     */
    public function forget(string $service, string $cacheKey): bool
    {
        $fullKey = $this->generateCacheKey($service, $cacheKey);
        return Cache::forget($fullKey);
    }
    
    /**
     * Clear all cache for a service
     *
     * @param string $service
     * @return void
     */
    public function flushService(string $service): void
    {
        $pattern = "ai:cache:{$service}:*";
        
        if (config('cache.default') === 'redis') {
            $redis = Cache::getRedis();
            $keys = $redis->keys($pattern);
            
            if (!empty($keys)) {
                $redis->del($keys);
                Log::info("AI Cache Flushed: {$service}", ['keys_deleted' => count($keys)]);
            }
        }
    }
    
    /**
     * Generate standardized cache key
     *
     * @param string $service
     * @param string $key
     * @return string
     */
    protected function generateCacheKey(string $service, string $key): string
    {
        return "ai:cache:{$service}:" . md5($key);
    }
    
    /**
     * Get cache statistics for a service
     *
     * @param string $service
     * @return array
     */
    public function getStats(string $service): array
    {
        // This would require Redis INFO command or custom tracking
        // For now, return basic info
        return [
            'service' => $service,
            'cache_enabled' => config('ai.enabled'),
            'cache_ttl' => config("ai.{$service}.cache_ttl", 300),
            'driver' => config('cache.default'),
        ];
    }
}
