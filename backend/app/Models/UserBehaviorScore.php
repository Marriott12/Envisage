<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserBehaviorScore extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'engagement_score',
        'purchase_propensity',
        'churn_risk',
        'lifetime_value_prediction',
        'user_segment',
        'preferences',
        'last_calculated_at',
    ];

    protected $casts = [
        'engagement_score' => 'decimal:2',
        'purchase_propensity' => 'decimal:2',
        'churn_risk' => 'decimal:2',
        'lifetime_value_prediction' => 'decimal:2',
        'preferences' => 'array',
        'last_calculated_at' => 'datetime',
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

    public function scopeHighEngagement($query)
    {
        return $query->where('engagement_score', '>=', 70);
    }

    public function scopeHighChurnRisk($query)
    {
        return $query->where('churn_risk', '>=', 70);
    }

    public function scopeHighPurchasePropensity($query)
    {
        return $query->where('purchase_propensity', '>=', 70);
    }

    // Helper Methods
    public function updateScores($scores)
    {
        $this->update(array_merge($scores, [
            'last_calculated_at' => now(),
        ]));
    }

    public function determineSegment()
    {
        if ($this->engagement_score >= 80 && $this->purchase_propensity >= 70) {
            return 'VIP';
        } elseif ($this->churn_risk >= 70) {
            return 'At Risk';
        } elseif ($this->engagement_score >= 50) {
            return 'Active';
        } elseif ($this->purchase_propensity >= 60) {
            return 'Potential Buyer';
        } else {
            return 'Inactive';
        }
    }

    public function recalculateSegment()
    {
        $this->user_segment = $this->determineSegment();
        $this->save();
    }
}
