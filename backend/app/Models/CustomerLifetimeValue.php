<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerLifetimeValue extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'historical_value',
        'predicted_value',
        'predicted_12_month',
        'predicted_24_month',
        'predicted_36_month',
        'avg_purchase_value',
        'purchase_frequency',
        'customer_lifespan_months',
        'profit_margin',
        'value_tier',
        'retention_probability',
        'growth_rate',
        'calculated_at'
    ];

    protected $casts = [
        'historical_value' => 'decimal:2',
        'predicted_value' => 'decimal:2',
        'predicted_12_month' => 'decimal:2',
        'predicted_24_month' => 'decimal:2',
        'predicted_36_month' => 'decimal:2',
        'avg_purchase_value' => 'decimal:2',
        'purchase_frequency' => 'decimal:2',
        'customer_lifespan_months' => 'decimal:2',
        'profit_margin' => 'decimal:2',
        'retention_probability' => 'decimal:4',
        'growth_rate' => 'decimal:4',
        'calculated_at' => 'datetime'
    ];

    /**
     * Relationships
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Calculate CLV for user
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

        // Historical value (actual)
        $historicalValue = $orders->sum('total_amount');

        // Average purchase value
        $avgPurchaseValue = $historicalValue / $orders->count();

        // Purchase frequency (per year)
        $firstOrder = $orders->min('created_at');
        $daysAsCustomer = now()->diffInDays($firstOrder);
        $purchaseFrequency = $daysAsCustomer > 0 ? ($orders->count() / $daysAsCustomer) * 365 : 0;

        // Customer lifespan estimate
        $customerLifespan = self::estimateLifespan($user, $orders);

        // Profit margin (configurable, default 30%)
        $profitMargin = 0.30;

        // Retention probability
        $churn = ChurnPrediction::where('user_id', $userId)->first();
        $retentionProb = $churn ? (1 - $churn->churn_probability) : 0.85;

        // Growth rate (spending trend)
        $growthRate = self::calculateGrowthRate($orders);

        // Predicted CLV
        $baseCLV = $avgPurchaseValue * $purchaseFrequency * $customerLifespan * $profitMargin;
        $predicted12Month = $avgPurchaseValue * $purchaseFrequency * $retentionProb * $profitMargin;
        $predicted24Month = $predicted12Month * 2 * pow($retentionProb, 2) * (1 + $growthRate);
        $predicted36Month = $predicted12Month * 3 * pow($retentionProb, 3) * (1 + $growthRate * 2);

        // Value tier
        $valueTier = self::determineValueTier($baseCLV);

        return CustomerLifetimeValue::updateOrCreate(
            ['user_id' => $userId],
            [
                'historical_value' => $historicalValue,
                'predicted_value' => $baseCLV,
                'predicted_12_month' => $predicted12Month,
                'predicted_24_month' => $predicted24Month,
                'predicted_36_month' => $predicted36Month,
                'avg_purchase_value' => $avgPurchaseValue,
                'purchase_frequency' => $purchaseFrequency,
                'customer_lifespan_months' => $customerLifespan,
                'profit_margin' => $profitMargin,
                'value_tier' => $valueTier,
                'retention_probability' => $retentionProb,
                'growth_rate' => $growthRate,
                'calculated_at' => now()
            ]
        );
    }

    /**
     * Estimate customer lifespan in months
     */
    protected static function estimateLifespan($user, $orders)
    {
        // If customer is churning, reduce lifespan
        $churn = ChurnPrediction::where('user_id', $user->id)->first();
        if ($churn && $churn->churn_risk === 'critical') {
            return 6; // 6 months
        }

        // Based on RFM segment
        $rfm = RfmScore::where('user_id', $user->id)->first();
        if ($rfm) {
            $segmentLifespans = [
                'Champions' => 60,
                'Loyal Customers' => 48,
                'Potential Loyalists' => 36,
                'At Risk' => 12,
                'Cannot Lose Them' => 24,
                'Hibernating' => 6,
                'Lost' => 3,
                'New Customers' => 24,
                'Promising' => 30
            ];

            return $segmentLifespans[$rfm->rfm_segment] ?? 24;
        }

        return 24; // Default 2 years
    }

    /**
     * Calculate growth rate from order trend
     */
    protected static function calculateGrowthRate($orders)
    {
        if ($orders->count() < 3) return 0;

        $sorted = $orders->sortBy('created_at')->values();
        $half = intval($sorted->count() / 2);

        $firstHalfAvg = $sorted->slice(0, $half)->avg('total_amount');
        $secondHalfAvg = $sorted->slice($half)->avg('total_amount');

        if ($firstHalfAvg == 0) return 0;

        $growth = ($secondHalfAvg - $firstHalfAvg) / $firstHalfAvg;
        return max(-0.5, min(0.5, $growth)); // Cap between -50% and +50%
    }

    /**
     * Determine value tier
     */
    protected static function determineValueTier($clv)
    {
        if ($clv >= 10000) return 'vip';
        if ($clv >= 5000) return 'platinum';
        if ($clv >= 2000) return 'gold';
        if ($clv >= 500) return 'silver';
        return 'bronze';
    }

    /**
     * Get CLV statistics
     */
    public static function getStatistics()
    {
        return [
            'total_customers' => self::count(),
            'by_tier' => self::selectRaw('value_tier, COUNT(*) as count, AVG(predicted_value) as avg_clv')
                ->groupBy('value_tier')
                ->get(),
            'total_predicted_value' => self::sum('predicted_value'),
            'avg_predicted_value' => self::avg('predicted_value'),
            'avg_retention_probability' => self::avg('retention_probability'),
            'high_value_customers' => self::whereIn('value_tier', ['platinum', 'vip'])->count()
        ];
    }

    /**
     * Scopes
     */
    public function scopeByTier($query, $tier)
    {
        return $query->where('value_tier', $tier);
    }

    public function scopeHighValue($query)
    {
        return $query->whereIn('value_tier', ['gold', 'platinum', 'vip']);
    }

    public function scopeVip($query)
    {
        return $query->where('value_tier', 'vip');
    }
}
