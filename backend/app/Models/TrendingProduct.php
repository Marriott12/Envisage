<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrendingProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'trending_date',
        'trending_score',
        'views_count',
        'purchases_count',
        'add_to_cart_count',
        'momentum',
        'rank',
    ];

    protected $casts = [
        'trending_date' => 'date',
        'trending_score' => 'decimal:2',
        'momentum' => 'decimal:2',
        'views_count' => 'integer',
        'purchases_count' => 'integer',
        'add_to_cart_count' => 'integer',
        'rank' => 'integer',
    ];

    // Relationships
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // Scopes
    public function scopeToday($query)
    {
        return $query->where('trending_date', today());
    }

    public function scopeTopTrending($query, $limit = 10)
    {
        return $query->orderByDesc('trending_score')->limit($limit);
    }

    // Helper methods
    public static function calculateTrending($date)
    {
        // Calculate trending score based on views, purchases, cart adds, and momentum
        $products = \DB::table('products')->pluck('id');

        foreach ($products as $productId) {
            // Today's stats
            $todayViews = \App\Models\AnalyticEvent::productViews()
                ->where('properties->product_id', $productId)
                ->whereDate('created_at', $date)
                ->count();

            $todayPurchases = \DB::table('order_items')
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->where('order_items.product_id', $productId)
                ->whereDate('orders.created_at', $date)
                ->where('orders.status', 'completed')
                ->count();

            $todayCartAdds = \App\Models\AnalyticEvent::addToCart()
                ->where('properties->product_id', $productId)
                ->whereDate('created_at', $date)
                ->count();

            // Yesterday's stats for momentum
            $yesterday = now()->parse($date)->subDay();
            $yesterdayViews = \App\Models\AnalyticEvent::productViews()
                ->where('properties->product_id', $productId)
                ->whereDate('created_at', $yesterday)
                ->count();

            // Calculate momentum (growth rate)
            $momentum = $yesterdayViews > 0 
                ? round((($todayViews - $yesterdayViews) / $yesterdayViews) * 100, 2)
                : 0;

            // Trending score formula: weighted sum of interactions + momentum bonus
            $trendingScore = ($todayViews * 1) + ($todayCartAdds * 3) + ($todayPurchases * 10) + ($momentum * 0.5);

            self::updateOrCreate(
                [
                    'product_id' => $productId,
                    'trending_date' => $date,
                ],
                [
                    'views_count' => $todayViews,
                    'purchases_count' => $todayPurchases,
                    'add_to_cart_count' => $todayCartAdds,
                    'momentum' => $momentum,
                    'trending_score' => $trendingScore,
                ]
            );
        }

        // Update ranks
        self::where('trending_date', $date)
            ->orderByDesc('trending_score')
            ->get()
            ->each(function ($trending, $index) {
                $trending->update(['rank' => $index + 1]);
            });
    }

    public static function getTrendingProducts($limit = 10, $date = null)
    {
        $date = $date ?? today();

        $trendingIds = self::where('trending_date', $date)
            ->orderBy('rank')
            ->limit($limit)
            ->pluck('product_id');

        return Product::whereIn('id', $trendingIds)
            ->get()
            ->sortBy(function ($product) use ($trendingIds) {
                return $trendingIds->search($product->id);
            })
            ->values();
    }
}
