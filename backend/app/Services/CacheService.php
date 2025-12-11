<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class CacheService
{
    /**
     * Cache product data
     */
    public function cacheProduct($product)
    {
        $key = "product:{$product->id}";
        Cache::put($key, $product, now()->addHours(24));
        
        return $product;
    }

    /**
     * Get cached product
     */
    public function getProduct($productId)
    {
        return Cache::remember("product:{$productId}", now()->addHours(24), function () use ($productId) {
            return \App\Models\Product::with(['category', 'seller', 'images'])
                ->findOrFail($productId);
        });
    }

    /**
     * Cache product list
     */
    public function cacheProductList($key, $products, $ttl = 3600)
    {
        Cache::put($key, $products, $ttl);
        return $products;
    }

    /**
     * Get cached product list
     */
    public function getProductList($key, $callback, $ttl = 3600)
    {
        return Cache::remember($key, $ttl, $callback);
    }

    /**
     * Cache search results
     */
    public function cacheSearchResults($query, $filters, $results)
    {
        $key = $this->generateSearchKey($query, $filters);
        Cache::put($key, $results, now()->addMinutes(30));
        
        return $results;
    }

    /**
     * Get cached search results
     */
    public function getSearchResults($query, $filters)
    {
        $key = $this->generateSearchKey($query, $filters);
        return Cache::get($key);
    }

    /**
     * Generate search cache key
     */
    protected function generateSearchKey($query, $filters)
    {
        return 'search:' . md5($query . json_encode($filters));
    }

    /**
     * Cache user cart
     */
    public function cacheCart($userId, $cart)
    {
        Cache::put("cart:{$userId}", $cart, now()->addDays(7));
        return $cart;
    }

    /**
     * Get cached cart
     */
    public function getCart($userId)
    {
        return Cache::get("cart:{$userId}");
    }

    /**
     * Cache user wishlist
     */
    public function cacheWishlist($userId, $wishlist)
    {
        Cache::put("wishlist:{$userId}", $wishlist, now()->addDays(7));
        return $wishlist;
    }

    /**
     * Get cached wishlist
     */
    public function getWishlist($userId)
    {
        return Cache::get("wishlist:{$userId}");
    }

    /**
     * Clear product cache
     */
    public function clearProductCache($productId)
    {
        Cache::forget("product:{$productId}");
    }

    /**
     * Clear category cache
     */
    public function clearCategoryCache($categoryId)
    {
        Cache::forget("category:{$categoryId}");
        Cache::forget("category:{$categoryId}:products");
    }

    /**
     * Clear user caches
     */
    public function clearUserCache($userId)
    {
        Cache::forget("cart:{$userId}");
        Cache::forget("wishlist:{$userId}");
        Cache::forget("user:{$userId}:orders");
    }

    /**
     * Cache popular products
     */
    public function cachePopularProducts($products)
    {
        Cache::put('products:popular', $products, now()->addHours(6));
        return $products;
    }

    /**
     * Cache trending products
     */
    public function cacheTrendingProducts($products)
    {
        Cache::put('products:trending', $products, now()->addHours(2));
        return $products;
    }

    /**
     * Increment view count (using Redis for real-time)
     */
    public function incrementViewCount($productId)
    {
        Redis::zincrby('product:views:daily', 1, $productId);
        Redis::zincrby('product:views:weekly', 1, $productId);
        Redis::zincrby('product:views:monthly', 1, $productId);
    }

    /**
     * Get trending products from Redis
     */
    public function getTrendingFromRedis($limit = 10)
    {
        $productIds = Redis::zrevrange('product:views:weekly', 0, $limit - 1);
        
        if (empty($productIds)) {
            return [];
        }

        return \App\Models\Product::whereIn('id', $productIds)
            ->with(['category', 'seller'])
            ->get()
            ->sortBy(function ($product) use ($productIds) {
                return array_search($product->id, $productIds);
            })
            ->values();
    }

    /**
     * Cache session data
     */
    public function cacheSession($sessionId, $data, $ttl = 3600)
    {
        Cache::put("session:{$sessionId}", $data, $ttl);
    }

    /**
     * Get cached session
     */
    public function getSession($sessionId)
    {
        return Cache::get("session:{$sessionId}");
    }

    /**
     * Clear expired sessions (cleanup job)
     */
    public function clearExpiredSessions()
    {
        // This would be called by a scheduled job
        $pattern = 'session:*';
        $keys = Redis::keys($pattern);
        
        foreach ($keys as $key) {
            if (!Redis::exists($key)) {
                Redis::del($key);
            }
        }
    }

    /**
     * Cache API response
     */
    public function cacheApiResponse($endpoint, $params, $response, $ttl = 300)
    {
        $key = 'api:' . md5($endpoint . json_encode($params));
        Cache::put($key, $response, $ttl);
        return $response;
    }

    /**
     * Get cached API response
     */
    public function getCachedApiResponse($endpoint, $params)
    {
        $key = 'api:' . md5($endpoint . json_encode($params));
        return Cache::get($key);
    }

    /**
     * Flush all cache
     */
    public function flushAll()
    {
        Cache::flush();
        Redis::flushdb();
    }

    /**
     * Get cache statistics
     */
    public function getStats()
    {
        return [
            'redis_connected' => Redis::ping(),
            'total_keys' => Redis::dbsize(),
            'memory_usage' => Redis::info('memory')['used_memory_human'] ?? 'N/A',
        ];
    }
}
