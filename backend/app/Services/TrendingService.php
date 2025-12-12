<?php

namespace App\Services;

use App\Models\TrendingProduct;
use App\Models\AnalyticEvent;
use App\Models\UserProductInteraction;
use Illuminate\Support\Facades\DB;

class TrendingService
{
    /**
     * Calculate trending products for a given date
     */
    public function calculateTrending($date = null)
    {
        $date = $date ?? today();
        TrendingProduct::calculateTrending($date);
    }

    /**
     * Get real-time trending products
     * Based on activity in last N hours
     */
    public function getRealTimeTrending($hours = 24, $limit = 10)
    {
        $cutoff = now()->subHours($hours);

        // Calculate real-time scores
        $products = DB::table('products')
            ->select('products.id')
            ->selectRaw('
                (SELECT COUNT(*) FROM analytic_events 
                 WHERE event_type = "product_view" 
                 AND JSON_EXTRACT(properties, "$.product_id") = products.id 
                 AND created_at >= ?) as views
            ', [$cutoff])
            ->selectRaw('
                (SELECT COUNT(*) FROM analytic_events 
                 WHERE event_type = "add_to_cart" 
                 AND JSON_EXTRACT(properties, "$.product_id") = products.id 
                 AND created_at >= ?) as cart_adds
            ', [$cutoff])
            ->selectRaw('
                (SELECT COUNT(*) FROM order_items 
                 JOIN orders ON order_items.order_id = orders.id 
                 WHERE order_items.product_id = products.id 
                 AND orders.created_at >= ? 
                 AND orders.status = "completed") as purchases
            ', [$cutoff])
            ->havingRaw('(views + cart_adds * 3 + purchases * 10) > 0')
            ->orderByRaw('(views + cart_adds * 3 + purchases * 10) DESC')
            ->limit($limit)
            ->get();

        return \App\Models\Product::whereIn('id', $products->pluck('id'))->get();
    }

    /**
     * Get trending products by category
     */
    public function getTrendingByCategory($categoryId, $limit = 10)
    {
        $today = today();

        $trendingIds = TrendingProduct::where('trending_date', $today)
            ->join('products', 'trending_products.product_id', '=', 'products.id')
            ->where('products.category_id', $categoryId)
            ->orderBy('trending_products.rank')
            ->limit($limit)
            ->pluck('trending_products.product_id');

        return \App\Models\Product::whereIn('id', $trendingIds)->get();
    }

    /**
     * Calculate momentum for a product
     * Measures growth rate compared to previous period
     */
    public function calculateMomentum($productId, $days = 7)
    {
        $currentPeriod = UserProductInteraction::where('product_id', $productId)
            ->where('interacted_at', '>=', now()->subDays($days))
            ->sum('interaction_weight');

        $previousPeriod = UserProductInteraction::where('product_id', $productId)
            ->whereBetween('interacted_at', [
                now()->subDays($days * 2),
                now()->subDays($days)
            ])
            ->sum('interaction_weight');

        if ($previousPeriod == 0) {
            return $currentPeriod > 0 ? 100 : 0;
        }

        return round((($currentPeriod - $previousPeriod) / $previousPeriod) * 100, 2);
    }

    /**
     * Get emerging trends (products with high momentum)
     */
    public function getEmergingTrends($limit = 10)
    {
        return TrendingProduct::where('trending_date', today())
            ->where('momentum', '>=', 50) // At least 50% growth
            ->orderByDesc('momentum')
            ->limit($limit)
            ->with('product')
            ->get()
            ->pluck('product');
    }

    /**
     * Schedule trending calculation
     * Should be run hourly
     */
    public function scheduleCalculation()
    {
        // Calculate for today
        $this->calculateTrending(today());

        // Clean up old trending data (keep last 30 days)
        TrendingProduct::where('trending_date', '<', now()->subDays(30))->delete();
    }
}
