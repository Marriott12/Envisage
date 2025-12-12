<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RfmScore extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'recency_days',
        'recency_score',
        'frequency_count',
        'frequency_score',
        'monetary_value',
        'monetary_score',
        'rfm_score',
        'rfm_segment',
        'avg_order_value',
        'total_orders',
        'first_purchase_at',
        'last_purchase_at'
    ];

    protected $casts = [
        'recency_days' => 'integer',
        'recency_score' => 'integer',
        'frequency_count' => 'integer',
        'frequency_score' => 'integer',
        'monetary_value' => 'decimal:2',
        'monetary_score' => 'integer',
        'avg_order_value' => 'decimal:2',
        'total_orders' => 'integer',
        'first_purchase_at' => 'datetime',
        'last_purchase_at' => 'datetime'
    ];

    /**
     * Relationships
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Calculate RFM score for user
     */
    public static function calculateForUser($userId)
    {
        $user = User::findOrFail($userId);
        $orders = Order::where('user_id', $userId)
            ->where('status', 'completed')
            ->get();

        if ($orders->isEmpty()) {
            return null;
        }

        // Recency: days since last purchase
        $lastPurchase = $orders->max('created_at');
        $recencyDays = now()->diffInDays($lastPurchase);

        // Frequency: number of purchases
        $frequencyCount = $orders->count();

        // Monetary: total spent
        $monetaryValue = $orders->sum('total_amount');

        // Calculate scores (1-5 scale)
        $recencyScore = self::scoreRecency($recencyDays);
        $frequencyScore = self::scoreFrequency($frequencyCount);
        $monetaryScore = self::scoreMonetary($monetaryValue);

        // Combined RFM score
        $rfmScore = "{$recencyScore}{$frequencyScore}{$monetaryScore}";
        $rfmSegment = self::determineSegment($recencyScore, $frequencyScore, $monetaryScore);

        // Additional metrics
        $avgOrderValue = $monetaryValue / $frequencyCount;
        $firstPurchase = $orders->min('created_at');

        return RfmScore::updateOrCreate(
            ['user_id' => $userId],
            [
                'recency_days' => $recencyDays,
                'recency_score' => $recencyScore,
                'frequency_count' => $frequencyCount,
                'frequency_score' => $frequencyScore,
                'monetary_value' => $monetaryValue,
                'monetary_score' => $monetaryScore,
                'rfm_score' => $rfmScore,
                'rfm_segment' => $rfmSegment,
                'avg_order_value' => $avgOrderValue,
                'total_orders' => $frequencyCount,
                'first_purchase_at' => $firstPurchase,
                'last_purchase_at' => $lastPurchase
            ]
        );
    }

    /**
     * Score recency (1-5, 5 is best = recent purchase)
     */
    protected static function scoreRecency($days)
    {
        if ($days <= 30) return 5;
        if ($days <= 60) return 4;
        if ($days <= 90) return 3;
        if ($days <= 180) return 2;
        return 1;
    }

    /**
     * Score frequency (1-5, 5 is best = many purchases)
     */
    protected static function scoreFrequency($count)
    {
        if ($count >= 20) return 5;
        if ($count >= 10) return 4;
        if ($count >= 5) return 3;
        if ($count >= 2) return 2;
        return 1;
    }

    /**
     * Score monetary (1-5, 5 is best = high spending)
     */
    protected static function scoreMonetary($value)
    {
        if ($value >= 5000) return 5;
        if ($value >= 2000) return 4;
        if ($value >= 1000) return 3;
        if ($value >= 500) return 2;
        return 1;
    }

    /**
     * Determine RFM segment
     */
    protected static function determineSegment($r, $f, $m)
    {
        $total = $r + $f + $m;

        // Champions: Best customers
        if ($r >= 4 && $f >= 4 && $m >= 4) {
            return 'Champions';
        }

        // Loyal Customers: Frequent buyers
        if ($f >= 4 && ($r >= 3 || $m >= 3)) {
            return 'Loyal Customers';
        }

        // Potential Loyalists: Recent customers with potential
        if ($r >= 4 && ($f >= 2 && $f <= 3) && ($m >= 2 && $m <= 3)) {
            return 'Potential Loyalists';
        }

        // New Customers: Recently acquired
        if ($r >= 4 && $f == 1 && $m >= 1) {
            return 'New Customers';
        }

        // At Risk: Used to be good, declining
        if (($r == 2 || $r == 3) && $f >= 3 && $m >= 3) {
            return 'At Risk';
        }

        // Can't Lose Them: High value, long time no see
        if ($r <= 2 && $f >= 4 && $m >= 4) {
            return 'Cannot Lose Them';
        }

        // Hibernating: Low engagement
        if ($r <= 2 && $f <= 2 && $m <= 2) {
            return 'Hibernating';
        }

        // Lost: Haven't purchased in long time
        if ($r == 1) {
            return 'Lost';
        }

        // Promising: Recent but not frequent
        if ($r >= 3 && $f <= 2) {
            return 'Promising';
        }

        return 'Needs Attention';
    }

    /**
     * Get segment distribution
     */
    public static function getSegmentDistribution()
    {
        return self::selectRaw('rfm_segment, COUNT(*) as count, AVG(monetary_value) as avg_value')
            ->groupBy('rfm_segment')
            ->orderBy('count', 'desc')
            ->get();
    }

    /**
     * Scopes
     */
    public function scopeBySegment($query, $segment)
    {
        return $query->where('rfm_segment', $segment);
    }

    public function scopeChampions($query)
    {
        return $query->where('rfm_segment', 'Champions');
    }

    public function scopeAtRisk($query)
    {
        return $query->whereIn('rfm_segment', ['At Risk', 'Cannot Lose Them']);
    }

    public function scopeHighValue($query)
    {
        return $query->where('monetary_score', '>=', 4);
    }
}
