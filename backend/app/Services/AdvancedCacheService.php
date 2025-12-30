<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class AdvancedCacheService
{
    protected int $defaultTtl = 3600; // 1 hour

    /**
     * Cache AI recommendation results
     */
    public function cacheRecommendations(int $userId, string $algorithm, array $recommendations): void
    {
        $key = "recommendations:{$userId}:{$algorithm}";
        Cache::put($key, $recommendations, now()->addHours(6));
        
        // Track cache write
        $this->trackMetric('recommendations', 'write');
    }

    /**
     * Get cached recommendations
     */
    public function getRecommendations(int $userId, string $algorithm): ?array
    {
        $key = "recommendations:{$userId}:{$algorithm}";
        $result = Cache::get($key);
        
        // Track cache hit/miss
        $this->trackMetric('recommendations', $result ? 'hit' : 'miss');
        
        return $result;
    }

    /**
     * Cache fraud analysis results
     */
    public function cacheFraudAnalysis(int $orderId, array $analysis): void
    {
        $key = "fraud:analysis:{$orderId}";
        Cache::put($key, $analysis, now()->addHours(24));
        
        $this->trackMetric('fraud', 'write');
    }

    /**
     * Get cached fraud analysis
     */
    public function getFraudAnalysis(int $orderId): ?array
    {
        $key = "fraud:analysis:{$orderId}";
        $result = Cache::get($key);
        
        $this->trackMetric('fraud', $result ? 'hit' : 'miss');
        
        return $result;
    }

    /**
     * Cache sentiment analysis results
     */
    public function cacheSentiment(int $productId, array $sentiment): void
    {
        $key = "sentiment:{$productId}";
        Cache::put($key, $sentiment, now()->addHours(12));
        
        $this->trackMetric('sentiment', 'write');
    }

    /**
     * Get cached sentiment
     */
    public function getSentiment(int $productId): ?array
    {
        $key = "sentiment:{$productId}";
        $result = Cache::get($key);
        
        $this->trackMetric('sentiment', $result ? 'hit' : 'miss');
        
        return $result;
    }

    /**
     * Cache product data
     */
    public function cacheProduct(int $productId, array $product): void
    {
        $key = "product:{$productId}";
        Cache::put($key, $product, now()->addHours(24));
        
        $this->trackMetric('product', 'write');
    }

    /**
     * Get cached product
     */
    public function getProduct(int $productId): ?array
    {
        $key = "product:{$productId}";
        $result = Cache::get($key);
        
        $this->trackMetric('product', $result ? 'hit' : 'miss');
        
        return $result;
    }

    /**
     * Cache API response
     */
    public function cacheResponse(string $endpoint, array $params, $response, int $ttl = null): void
    {
        $key = $this->generateCacheKey($endpoint, $params);
        Cache::put($key, $response, now()->addSeconds($ttl ?? $this->defaultTtl));
        
        $this->trackMetric('api_response', 'write');
    }

    /**
     * Get cached API response
     */
    public function getResponse(string $endpoint, array $params)
    {
        $key = $this->generateCacheKey($endpoint, $params);
        $result = Cache::get($key);
        
        $this->trackMetric('api_response', $result ? 'hit' : 'miss');
        
        return $result;
    }

    /**
     * Invalidate product cache
     */
    public function invalidateProduct(int $productId): void
    {
        Cache::forget("product:{$productId}");
        
        // Also clear related caches
        Cache::forget("sentiment:{$productId}");
        
        // Clear recommendation caches that might include this product
        $this->clearRecommendationsCache();
    }

    /**
     * Invalidate user recommendations
     */
    public function invalidateUserRecommendations(int $userId): void
    {
        $algorithms = ['neural', 'bandit', 'session', 'context', 'hybrid'];
        
        foreach ($algorithms as $algorithm) {
            Cache::forget("recommendations:{$userId}:{$algorithm}");
        }
    }

    /**
     * Clear all recommendation caches
     */
    public function clearRecommendationsCache(): void
    {
        $pattern = "recommendations:*";
        $this->clearByPattern($pattern);
    }

    /**
     * Warm up cache with popular products
     */
    public function warmCache(): void
    {
        // Get popular products from database
        $popularProducts = \App\Models\Product::query()
            ->where('status', 'active')
            ->orderBy('view_count', 'desc')
            ->limit(100)
            ->get();

        foreach ($popularProducts as $product) {
            $this->cacheProduct($product->id, $product->toArray());
        }

        info('Cache warmed with ' . $popularProducts->count() . ' popular products');
    }

    /**
     * Get cache statistics
     */
    public function getStats(): array
    {
        $types = ['recommendations', 'fraud', 'sentiment', 'product', 'api_response'];
        $stats = [];

        foreach ($types as $type) {
            $hits = Cache::get("metrics:{$type}:hit", 0);
            $misses = Cache::get("metrics:{$type}:miss", 0);
            $total = $hits + $misses;
            
            $stats[$type] = [
                'hits' => $hits,
                'misses' => $misses,
                'total' => $total,
                'hit_rate' => $total > 0 ? round(($hits / $total) * 100, 2) : 0,
            ];
        }

        return $stats;
    }

    /**
     * Clear cache by pattern (Redis only)
     */
    protected function clearByPattern(string $pattern): void
    {
        if (config('cache.default') === 'redis') {
            $keys = Redis::keys($pattern);
            if (!empty($keys)) {
                Redis::del($keys);
            }
        }
    }

    /**
     * Track cache metrics
     */
    protected function trackMetric(string $type, string $action): void
    {
        $key = "metrics:{$type}:{$action}";
        Cache::increment($key);
        
        // Set expiry on first write
        if (!Cache::has($key . ':ttl')) {
            Cache::put($key . ':ttl', true, now()->addDay());
        }
    }

    /**
     * Generate cache key from endpoint and params
     */
    protected function generateCacheKey(string $endpoint, array $params): string
    {
        ksort($params);
        $paramsHash = md5(json_encode($params));
        return "api:" . str_replace('/', ':', $endpoint) . ":{$paramsHash}";
    }

    /**
     * Remember (get from cache or execute callback)
     */
    public function remember(string $key, int $ttl, callable $callback)
    {
        return Cache::remember($key, $ttl, $callback);
    }

    /**
     * Flush entire cache
     */
    public function flush(): bool
    {
        return Cache::flush();
    }
}
