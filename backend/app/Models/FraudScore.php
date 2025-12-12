<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FraudScore extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'user_id',
        'total_score',
        'risk_level',
        'triggered_rules',
        'score_breakdown',
        'analysis_data',
        'status',
        'reviewed_by',
        'reviewed_at',
        'review_notes',
        'false_positive'
    ];

    protected $casts = [
        'triggered_rules' => 'array',
        'score_breakdown' => 'array',
        'analysis_data' => 'array',
        'total_score' => 'integer',
        'reviewed_at' => 'datetime',
        'false_positive' => 'boolean'
    ];

    /**
     * Relationships
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Calculate risk level based on score
     */
    public function calculateRiskLevel()
    {
        if ($this->total_score >= 80) {
            return 'critical';
        } elseif ($this->total_score >= 60) {
            return 'high';
        } elseif ($this->total_score >= 40) {
            return 'medium';
        } else {
            return 'low';
        }
    }

    /**
     * Determine action based on risk level
     */
    public function getRecommendedAction()
    {
        switch ($this->risk_level) {
            case 'critical':
                return 'block';
            case 'high':
                return 'review';
            case 'medium':
                return 'flag';
            case 'low':
            default:
                return 'approve';
        }
    }

    /**
     * Approve the order
     */
    public function approve($reviewerId = null, $notes = null)
    {
        $this->update([
            'status' => 'approved',
            'reviewed_by' => $reviewerId,
            'reviewed_at' => now(),
            'review_notes' => $notes
        ]);

        // Update order status if needed
        if ($this->order && $this->order->status === 'pending_fraud_review') {
            $this->order->update(['status' => 'processing']);
        }
    }

    /**
     * Reject the order
     */
    public function reject($reviewerId = null, $notes = null)
    {
        $this->update([
            'status' => 'rejected',
            'reviewed_by' => $reviewerId,
            'reviewed_at' => now(),
            'review_notes' => $notes
        ]);

        // Update order status
        if ($this->order) {
            $this->order->update(['status' => 'cancelled']);
        }
    }

    /**
     * Mark as false positive (for learning)
     */
    public function markAsFalsePositive($reviewerId = null, $notes = null)
    {
        $this->update([
            'false_positive' => true,
            'status' => 'approved',
            'reviewed_by' => $reviewerId,
            'reviewed_at' => now(),
            'review_notes' => $notes
        ]);

        // Update order status
        if ($this->order && $this->order->status === 'pending_fraud_review') {
            $this->order->update(['status' => 'processing']);
        }
    }

    /**
     * Get fraud analysis summary
     */
    public function getAnalysisSummary()
    {
        $triggeredRuleObjects = FraudRule::whereIn('id', $this->triggered_rules ?? [])->get();

        return [
            'order_id' => $this->order_id,
            'total_score' => $this->total_score,
            'risk_level' => $this->risk_level,
            'recommended_action' => $this->getRecommendedAction(),
            'triggered_rules' => $triggeredRuleObjects->map(function($rule) {
                return [
                    'id' => $rule->id,
                    'name' => $rule->name,
                    'type' => $rule->rule_type,
                    'score_contribution' => $rule->risk_score
                ];
            }),
            'score_breakdown' => $this->score_breakdown,
            'analysis_data' => $this->analysis_data,
            'status' => $this->status
        ];
    }

    /**
     * Scopes
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeUnderReview($query)
    {
        return $query->where('status', 'under_review');
    }

    public function scopeHighRisk($query)
    {
        return $query->whereIn('risk_level', ['high', 'critical']);
    }

    public function scopeByRiskLevel($query, $level)
    {
        return $query->where('risk_level', $level);
    }

    public function scopeFalsePositives($query)
    {
        return $query->where('false_positive', true);
    }

    /**
     * Get statistics for dashboard
     */
    public static function getStatistics($days = 30)
    {
        $startDate = now()->subDays($days);

        return [
            'total_analyzed' => self::where('created_at', '>', $startDate)->count(),
            'by_risk_level' => self::where('created_at', '>', $startDate)
                ->selectRaw('risk_level, COUNT(*) as count')
                ->groupBy('risk_level')
                ->pluck('count', 'risk_level'),
            'by_status' => self::where('created_at', '>', $startDate)
                ->selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status'),
            'blocked_orders' => self::where('created_at', '>', $startDate)
                ->where('status', 'rejected')
                ->count(),
            'false_positives' => self::where('created_at', '>', $startDate)
                ->where('false_positive', true)
                ->count(),
            'avg_score' => self::where('created_at', '>', $startDate)
                ->avg('total_score'),
            'pending_review' => self::whereIn('status', ['pending', 'under_review'])->count()
        ];
    }
}
