<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChurnPrediction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'churn_probability',
        'churn_risk',
        'contributing_factors',
        'recommended_actions',
        'days_until_predicted_churn',
        'predicted_lifetime_value',
        'intervention_triggered',
        'intervention_at',
        'intervention_type',
        'predicted_at'
    ];

    protected $casts = [
        'churn_probability' => 'decimal:4',
        'contributing_factors' => 'array',
        'recommended_actions' => 'array',
        'days_until_predicted_churn' => 'integer',
        'predicted_lifetime_value' => 'decimal:2',
        'intervention_triggered' => 'boolean',
        'intervention_at' => 'datetime',
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
     * Predict churn for user
     */
    public static function predictForUser($userId)
    {
        $user = User::findOrFail($userId);
        
        // Gather user data
        $data = self::gatherUserData($user);
        
        // Calculate churn probability using ML model
        $probability = self::calculateChurnProbability($data);
        
        // Determine risk level
        $risk = self::determineRiskLevel($probability);
        
        // Identify contributing factors
        $factors = self::identifyContributingFactors($data);
        
        // Generate recommended actions
        $actions = self::generateRetentionStrategies($risk, $factors);
        
        // Predict days until churn
        $daysUntilChurn = self::predictDaysUntilChurn($probability, $data);
        
        // Predict remaining CLV
        $clv = CustomerLifetimeValue::where('user_id', $userId)->first();
        $predictedValue = $clv ? $clv->predicted_12_month * (1 - $probability) : 0;

        return ChurnPrediction::updateOrCreate(
            ['user_id' => $userId],
            [
                'churn_probability' => $probability,
                'churn_risk' => $risk,
                'contributing_factors' => $factors,
                'recommended_actions' => $actions,
                'days_until_predicted_churn' => $daysUntilChurn,
                'predicted_lifetime_value' => $predictedValue,
                'predicted_at' => now()
            ]
        );
    }

    /**
     * Gather user data for prediction
     */
    protected static function gatherUserData($user)
    {
        $orders = Order::where('user_id', $user->id)
            ->where('status', 'completed')
            ->get();

        if ($orders->isEmpty()) {
            return null;
        }

        $lastOrder = $orders->sortByDesc('created_at')->first();
        $daysSinceLastOrder = now()->diffInDays($lastOrder->created_at);
        
        return [
            'days_since_last_order' => $daysSinceLastOrder,
            'total_orders' => $orders->count(),
            'avg_order_value' => $orders->avg('total_amount'),
            'total_spent' => $orders->sum('total_amount'),
            'order_frequency' => self::calculateOrderFrequency($orders),
            'days_as_customer' => now()->diffInDays($user->created_at),
            'declining_frequency' => self::isFrequencyDeclining($orders),
            'declining_value' => self::isValueDeclining($orders),
            'has_recent_support_ticket' => false, // TODO: Integrate support system
            'engagement_score' => self::calculateEngagementScore($user)
        ];
    }

    /**
     * Calculate churn probability (ML model simulation)
     */
    protected static function calculateChurnProbability($data)
    {
        if (!$data) return 0.9; // High churn if no data

        $score = 0;

        // Days since last order (strong indicator)
        if ($data['days_since_last_order'] > 180) $score += 0.4;
        elseif ($data['days_since_last_order'] > 90) $score += 0.25;
        elseif ($data['days_since_last_order'] > 60) $score += 0.15;
        elseif ($data['days_since_last_order'] > 30) $score += 0.05;

        // Declining frequency
        if ($data['declining_frequency']) $score += 0.2;

        // Declining order value
        if ($data['declining_value']) $score += 0.15;

        // Low engagement
        if ($data['engagement_score'] < 0.3) $score += 0.15;

        // Total orders (loyal customers less likely to churn)
        if ($data['total_orders'] < 2) $score += 0.1;
        elseif ($data['total_orders'] >= 10) $score -= 0.1;

        return max(0, min(1, $score));
    }

    /**
     * Determine risk level from probability
     */
    protected static function determineRiskLevel($probability)
    {
        if ($probability >= 0.75) return 'critical';
        if ($probability >= 0.5) return 'high';
        if ($probability >= 0.25) return 'medium';
        return 'low';
    }

    /**
     * Identify contributing factors
     */
    protected static function identifyContributingFactors($data)
    {
        if (!$data) return ['No purchase history'];

        $factors = [];

        if ($data['days_since_last_order'] > 90) {
            $factors[] = "No purchase in {$data['days_since_last_order']} days";
        }

        if ($data['declining_frequency']) {
            $factors[] = "Declining purchase frequency";
        }

        if ($data['declining_value']) {
            $factors[] = "Declining order values";
        }

        if ($data['engagement_score'] < 0.3) {
            $factors[] = "Low engagement with platform";
        }

        if ($data['total_orders'] < 2) {
            $factors[] = "Limited purchase history";
        }

        return $factors;
    }

    /**
     * Generate retention strategies
     */
    protected static function generateRetentionStrategies($risk, $factors)
    {
        $actions = [];

        if ($risk === 'critical' || $risk === 'high') {
            $actions[] = "Send win-back email campaign";
            $actions[] = "Offer 20% discount on next order";
            $actions[] = "Personalized product recommendations";
            $actions[] = "Request feedback via survey";
        }

        if ($risk === 'medium') {
            $actions[] = "Send re-engagement email";
            $actions[] = "Offer 10% discount";
            $actions[] = "Highlight new products in their categories";
        }

        if ($risk === 'low') {
            $actions[] = "Regular newsletter";
            $actions[] = "Loyalty program invitation";
        }

        return $actions;
    }

    /**
     * Predict days until churn
     */
    protected static function predictDaysUntilChurn($probability, $data)
    {
        if (!$data || $probability < 0.25) return null;

        // Estimate based on current trend
        $avgDaysBetweenOrders = $data['order_frequency'] * 365;
        $daysSinceLastOrder = $data['days_since_last_order'];
        
        $threshold = $avgDaysBetweenOrders * 2; // Churn if 2x normal frequency
        $remaining = max(0, $threshold - $daysSinceLastOrder);

        return round($remaining);
    }

    /**
     * Calculate order frequency (orders per year)
     */
    protected static function calculateOrderFrequency($orders)
    {
        if ($orders->count() < 2) return 0;

        $firstOrder = $orders->min('created_at');
        $lastOrder = $orders->max('created_at');
        $daysSpan = $firstOrder->diffInDays($lastOrder);

        if ($daysSpan == 0) return 0;

        $ordersPerDay = $orders->count() / $daysSpan;
        return $ordersPerDay * 365;
    }

    /**
     * Check if order frequency is declining
     */
    protected static function isFrequencyDeclining($orders)
    {
        if ($orders->count() < 4) return false;

        $sorted = $orders->sortBy('created_at')->values();
        $half = intval($sorted->count() / 2);

        $firstHalf = $sorted->slice(0, $half);
        $secondHalf = $sorted->slice($half);

        $firstHalfSpan = $firstHalf->first()->created_at->diffInDays($firstHalf->last()->created_at);
        $secondHalfSpan = $secondHalf->first()->created_at->diffInDays($secondHalf->last()->created_at);

        if ($firstHalfSpan == 0 || $secondHalfSpan == 0) return false;

        $firstFreq = $firstHalf->count() / $firstHalfSpan;
        $secondFreq = $secondHalf->count() / $secondHalfSpan;

        return $secondFreq < $firstFreq * 0.7; // 30% decline
    }

    /**
     * Check if order value is declining
     */
    protected static function isValueDeclining($orders)
    {
        if ($orders->count() < 4) return false;

        $sorted = $orders->sortBy('created_at')->values();
        $half = intval($sorted->count() / 2);

        $firstHalfAvg = $sorted->slice(0, $half)->avg('total_amount');
        $secondHalfAvg = $sorted->slice($half)->avg('total_amount');

        return $secondHalfAvg < $firstHalfAvg * 0.7; // 30% decline
    }

    /**
     * Calculate engagement score
     */
    protected static function calculateEngagementScore($user)
    {
        // TODO: Integrate with user activity tracking
        // For now, simple calculation based on recent activity
        $recentViews = 0; // ProductView::where('user_id', $user->id)->where('created_at', '>', now()->subDays(30))->count();
        $recentLogins = 0; // ActivityLog::where('user_id', $user->id)->where('activity', 'login')->where('created_at', '>', now()->subDays(30))->count();
        
        $score = 0;
        $score += min(0.5, $recentViews / 100); // Up to 0.5 for views
        $score += min(0.5, $recentLogins / 20); // Up to 0.5 for logins

        return $score;
    }

    /**
     * Trigger retention intervention
     */
    public function triggerIntervention($type = 'email')
    {
        $this->update([
            'intervention_triggered' => true,
            'intervention_at' => now(),
            'intervention_type' => $type
        ]);

        // TODO: Trigger actual intervention (email, notification, etc.)
    }

    /**
     * Scopes
     */
    public function scopeHighRisk($query)
    {
        return $query->whereIn('churn_risk', ['high', 'critical']);
    }

    public function scopeNeedsIntervention($query)
    {
        return $query->whereIn('churn_risk', ['high', 'critical'])
            ->where('intervention_triggered', false);
    }

    public function scopeByRisk($query, $risk)
    {
        return $query->where('churn_risk', $risk);
    }
}
