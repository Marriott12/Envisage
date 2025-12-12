<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserPreference extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'favorite_categories',
        'favorite_brands',
        'price_range',
        'preferred_attributes',
        'avg_purchase_amount',
        'purchase_frequency',
        'user_segment',
        'last_updated_at',
    ];

    protected $casts = [
        'favorite_categories' => 'array',
        'favorite_brands' => 'array',
        'price_range' => 'array',
        'preferred_attributes' => 'array',
        'avg_purchase_amount' => 'decimal:2',
        'purchase_frequency' => 'integer',
        'last_updated_at' => 'datetime',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeBySegment($query, $segment)
    {
        return $query->where('user_segment', $segment);
    }

    public function scopeBudgetSegment($query)
    {
        return $query->where('user_segment', 'budget');
    }

    public function scopeMidRangeSegment($query)
    {
        return $query->where('user_segment', 'mid-range');
    }

    public function scopeLuxurySegment($query)
    {
        return $query->where('user_segment', 'luxury');
    }

    // Helper methods
    public static function updatePreferences($userId)
    {
        $user = User::find($userId);
        if (!$user) return null;

        // Get favorite categories (top 5 most purchased)
        $favoriteCategories = \DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->where('orders.user_id', $userId)
            ->where('orders.status', 'completed')
            ->selectRaw('products.category_id, COUNT(*) as count')
            ->groupBy('products.category_id')
            ->orderByDesc('count')
            ->limit(5)
            ->pluck('count', 'category_id')
            ->toArray();

        // Calculate price range preferences
        $prices = \DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.user_id', $userId)
            ->where('orders.status', 'completed')
            ->pluck('price');

        $priceRange = $prices->count() > 0 ? [
            'min' => $prices->min(),
            'max' => $prices->max(),
            'avg' => round($prices->avg(), 2),
        ] : null;

        // Calculate purchase metrics
        $orders = \DB::table('orders')
            ->where('user_id', $userId)
            ->where('status', 'completed')
            ->get();

        $avgPurchaseAmount = $orders->avg('total') ?? 0;
        $firstOrderDate = $orders->min('created_at');
        $monthsSinceFirst = $firstOrderDate ? now()->diffInMonths($firstOrderDate) : 1;
        $purchaseFrequency = $orders->count() / max($monthsSinceFirst, 1);

        // Determine segment
        $segment = 'mid-range';
        if ($avgPurchaseAmount < 50) {
            $segment = 'budget';
        } elseif ($avgPurchaseAmount > 200) {
            $segment = 'luxury';
        }

        return self::updateOrCreate(
            ['user_id' => $userId],
            [
                'favorite_categories' => $favoriteCategories,
                'price_range' => $priceRange,
                'avg_purchase_amount' => round($avgPurchaseAmount, 2),
                'purchase_frequency' => round($purchaseFrequency, 2),
                'user_segment' => $segment,
                'last_updated_at' => now(),
            ]
        );
    }
}
