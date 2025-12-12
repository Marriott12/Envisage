<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NextPurchasePrediction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'predicted_date',
        'days_until_purchase',
        'confidence_score',
        'predicted_categories',
        'predicted_products',
        'predicted_order_value',
        'notification_sent',
        'notification_sent_at',
        'prediction_accurate',
        'actual_purchase_at',
        'predicted_at'
    ];

    protected $casts = [
        'predicted_date' => 'date',
        'days_until_purchase' => 'integer',
        'confidence_score' => 'decimal:4',
        'predicted_categories' => 'array',
        'predicted_products' => 'array',
        'predicted_order_value' => 'decimal:2',
        'notification_sent' => 'boolean',
        'notification_sent_at' => 'datetime',
        'prediction_accurate' => 'boolean',
        'actual_purchase_at' => 'datetime',
        'predicted_at' => 'datetime'
    ];

    /**
     * Relationships
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Predict next purchase for user
     */
    public static function predictForUser($userId)
    {
        $user = User::findOrFail($userId);
        $orders = Order::where('user_id', $userId)
            ->where('status', 'completed')
            ->orderBy('created_at', 'desc')
            ->get();

        if ($orders->count() < 2) {
            return null; // Need at least 2 orders to predict
        }

        // Calculate average days between purchases
        $daysBetween = self::calculateAverageDaysBetween($orders);
        
        // Get last purchase date
        $lastPurchase = $orders->first()->created_at;
        
        // Predict next purchase date
        $predictedDate = $lastPurchase->copy()->addDays($daysBetween);
        $daysUntil = now()->diffInDays($predictedDate, false);

        // Calculate confidence score
        $confidence = self::calculateConfidence($orders, $daysBetween);

        // Predict categories and products
        $predictedCategories = self::predictCategories($userId, $orders);
        $predictedProducts = self::predictProducts($userId, $orders);

        // Predict order value
        $predictedValue = self::predictOrderValue($orders);

        return NextPurchasePrediction::create([
            'user_id' => $userId,
            'predicted_date' => $predictedDate,
            'days_until_purchase' => max(0, $daysUntil),
            'confidence_score' => $confidence,
            'predicted_categories' => $predictedCategories,
            'predicted_products' => $predictedProducts,
            'predicted_order_value' => $predictedValue,
            'predicted_at' => now()
        ]);
    }

    /**
     * Calculate average days between purchases
     */
    protected static function calculateAverageDaysBetween($orders)
    {
        if ($orders->count() < 2) return 30; // Default

        $intervals = [];
        for ($i = 0; $i < $orders->count() - 1; $i++) {
            $days = $orders[$i]->created_at->diffInDays($orders[$i + 1]->created_at);
            $intervals[] = $days;
        }

        return array_sum($intervals) / count($intervals);
    }

    /**
     * Calculate prediction confidence
     */
    protected static function calculateConfidence($orders, $avgDays)
    {
        if ($orders->count() < 3) return 0.3;

        // More orders = higher confidence
        $orderConfidence = min(0.4, $orders->count() / 25);

        // Consistent intervals = higher confidence
        $intervals = [];
        for ($i = 0; $i < $orders->count() - 1; $i++) {
            $days = $orders[$i]->created_at->diffInDays($orders[$i + 1]->created_at);
            $intervals[] = $days;
        }

        $stdDev = self::standardDeviation($intervals);
        $consistencyScore = $stdDev > 0 ? 1 / (1 + ($stdDev / $avgDays)) : 1;
        $consistencyConfidence = $consistencyScore * 0.4;

        // Recent activity = higher confidence
        $daysSinceLastOrder = now()->diffInDays($orders->first()->created_at);
        $recencyConfidence = $daysSinceLastOrder < $avgDays ? 0.2 : 0.1;

        return $orderConfidence + $consistencyConfidence + $recencyConfidence;
    }

    /**
     * Predict likely categories
     */
    protected static function predictCategories($userId, $orders)
    {
        // Get categories from recent orders
        $categories = [];
        foreach ($orders->take(5) as $order) {
            foreach ($order->items as $item) {
                if ($item->product && $item->product->category_id) {
                    $categoryId = $item->product->category_id;
                    if (!isset($categories[$categoryId])) {
                        $categories[$categoryId] = 0;
                    }
                    $categories[$categoryId]++;
                }
            }
        }

        // Sort by frequency
        arsort($categories);
        return array_slice(array_keys($categories), 0, 3); // Top 3
    }

    /**
     * Predict likely products
     */
    protected static function predictProducts($userId, $orders)
    {
        // Get frequently purchased products
        $products = [];
        foreach ($orders as $order) {
            foreach ($order->items as $item) {
                if ($item->product_id) {
                    if (!isset($products[$item->product_id])) {
                        $products[$item->product_id] = 0;
                    }
                    $products[$item->product_id]++;
                }
            }
        }

        // Sort by frequency
        arsort($products);
        return array_slice(array_keys($products), 0, 5); // Top 5
    }

    /**
     * Predict order value
     */
    protected static function predictOrderValue($orders)
    {
        // Average of recent orders with growth trend
        $recentOrders = $orders->take(5);
        $avgValue = $recentOrders->avg('total_amount');

        // Apply growth rate if increasing
        $sorted = $recentOrders->sortBy('created_at')->values();
        if ($sorted->count() >= 3) {
            $firstHalf = $sorted->slice(0, 2)->avg('total_amount');
            $secondHalf = $sorted->slice(2)->avg('total_amount');
            
            if ($secondHalf > $firstHalf) {
                $growthRate = ($secondHalf - $firstHalf) / $firstHalf;
                $avgValue = $avgValue * (1 + ($growthRate * 0.5)); // 50% of growth
            }
        }

        return $avgValue;
    }

    /**
     * Calculate standard deviation
     */
    protected static function standardDeviation($values)
    {
        if (count($values) < 2) return 0;

        $mean = array_sum($values) / count($values);
        $squaredDiffs = array_map(function($v) use ($mean) {
            return pow($v - $mean, 2);
        }, $values);

        $variance = array_sum($squaredDiffs) / count($values);
        return sqrt($variance);
    }

    /**
     * Mark notification as sent
     */
    public function markNotificationSent()
    {
        $this->update([
            'notification_sent' => true,
            'notification_sent_at' => now()
        ]);
    }

    /**
     * Verify prediction accuracy (call after actual purchase)
     */
    public function verifyAccuracy(Order $order)
    {
        $predictedDate = $this->predicted_date;
        $actualDate = $order->created_at->toDateString();
        
        // Consider accurate if within 7 days
        $daysDiff = abs($this->predicted_date->diffInDays($order->created_at));
        $accurate = $daysDiff <= 7;

        $this->update([
            'prediction_accurate' => $accurate,
            'actual_purchase_at' => $order->created_at
        ]);
    }

    /**
     * Get predictions due for notification
     */
    public static function getDueForNotification($daysBeforeDate = 3)
    {
        return self::where('notification_sent', false)
            ->where('predicted_date', '>=', now()->toDateString())
            ->where('predicted_date', '<=', now()->addDays($daysBeforeDate)->toDateString())
            ->where('confidence_score', '>=', 0.5)
            ->with('user')
            ->get();
    }

    /**
     * Get accuracy statistics
     */
    public static function getAccuracyStats()
    {
        $total = self::whereNotNull('prediction_accurate')->count();
        $accurate = self::where('prediction_accurate', true)->count();

        return [
            'total_verified' => $total,
            'accurate_predictions' => $accurate,
            'accuracy_rate' => $total > 0 ? ($accurate / $total) : 0,
            'avg_confidence' => self::where('prediction_accurate', true)->avg('confidence_score')
        ];
    }

    /**
     * Scopes
     */
    public function scopeUpcoming($query, $days = 7)
    {
        return $query->where('predicted_date', '>=', now()->toDateString())
            ->where('predicted_date', '<=', now()->addDays($days)->toDateString());
    }

    public function scopeHighConfidence($query)
    {
        return $query->where('confidence_score', '>=', 0.7);
    }

    public function scopeNotNotified($query)
    {
        return $query->where('notification_sent', false);
    }
}
