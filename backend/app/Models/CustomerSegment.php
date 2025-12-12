<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerSegment extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'segment_type',
        'criteria',
        'customer_count',
        'avg_lifetime_value',
        'avg_order_value',
        'avg_order_frequency',
        'is_active',
        'last_calculated_at'
    ];

    protected $casts = [
        'criteria' => 'array',
        'customer_count' => 'integer',
        'avg_lifetime_value' => 'decimal:2',
        'avg_order_value' => 'decimal:2',
        'avg_order_frequency' => 'decimal:2',
        'is_active' => 'boolean',
        'last_calculated_at' => 'datetime'
    ];

    /**
     * Relationships
     */
    public function memberships()
    {
        return $this->hasMany(CustomerSegmentMembership::class, 'segment_id');
    }

    public function customers()
    {
        return $this->belongsToMany(User::class, 'customer_segment_memberships', 'segment_id', 'user_id')
            ->withPivot('segment_data', 'joined_at')
            ->withTimestamps();
    }

    /**
     * Calculate segment membership
     */
    public function calculateMembership()
    {
        // Remove all existing memberships
        CustomerSegmentMembership::where('segment_id', $this->id)->delete();

        $users = $this->evaluateCriteria();
        
        $memberships = [];
        foreach ($users as $user) {
            $memberships[] = [
                'user_id' => $user->id,
                'segment_id' => $this->id,
                'segment_data' => $this->getSegmentDataForUser($user),
                'joined_at' => now(),
                'created_at' => now(),
                'updated_at' => now()
            ];
        }

        if (!empty($memberships)) {
            CustomerSegmentMembership::insert($memberships);
        }

        // Update segment statistics
        $this->update([
            'customer_count' => count($memberships),
            'last_calculated_at' => now()
        ]);

        return count($memberships);
    }

    /**
     * Evaluate criteria to find matching users
     */
    protected function evaluateCriteria()
    {
        $criteria = $this->criteria;
        
        switch ($this->segment_type) {
            case 'rfm':
                return $this->evaluateRfmCriteria($criteria);
            
            case 'behavioral':
                return $this->evaluateBehavioralCriteria($criteria);
            
            case 'demographic':
                return $this->evaluateDemographicCriteria($criteria);
            
            case 'predictive':
                return $this->evaluatePredictiveCriteria($criteria);
            
            case 'custom':
                return $this->evaluateCustomCriteria($criteria);
            
            default:
                return collect([]);
        }
    }

    /**
     * Evaluate RFM criteria
     */
    protected function evaluateRfmCriteria($criteria)
    {
        $query = RfmScore::query();

        if (isset($criteria['rfm_segments'])) {
            $query->whereIn('rfm_segment', $criteria['rfm_segments']);
        }

        if (isset($criteria['min_recency_score'])) {
            $query->where('recency_score', '>=', $criteria['min_recency_score']);
        }

        if (isset($criteria['min_frequency_score'])) {
            $query->where('frequency_score', '>=', $criteria['min_frequency_score']);
        }

        if (isset($criteria['min_monetary_score'])) {
            $query->where('monetary_score', '>=', $criteria['min_monetary_score']);
        }

        return $query->with('user')->get()->pluck('user');
    }

    /**
     * Evaluate behavioral criteria
     */
    protected function evaluateBehavioralCriteria($criteria)
    {
        $query = User::query();

        if (isset($criteria['min_orders'])) {
            $query->whereHas('orders', function($q) use ($criteria) {
                $q->havingRaw('COUNT(*) >= ?', [$criteria['min_orders']]);
            });
        }

        if (isset($criteria['last_purchase_days'])) {
            $query->whereHas('orders', function($q) use ($criteria) {
                $q->where('created_at', '>=', now()->subDays($criteria['last_purchase_days']));
            });
        }

        return $query->get();
    }

    /**
     * Evaluate demographic criteria
     */
    protected function evaluateDemographicCriteria($criteria)
    {
        $query = User::query();

        if (isset($criteria['countries'])) {
            $query->whereIn('country', $criteria['countries']);
        }

        if (isset($criteria['registration_days'])) {
            $query->where('created_at', '>=', now()->subDays($criteria['registration_days']));
        }

        return $query->get();
    }

    /**
     * Evaluate predictive criteria
     */
    protected function evaluatePredictiveCriteria($criteria)
    {
        if (isset($criteria['churn_risk'])) {
            $userIds = ChurnPrediction::whereIn('churn_risk', $criteria['churn_risk'])
                ->pluck('user_id');
            return User::whereIn('id', $userIds)->get();
        }

        if (isset($criteria['clv_tier'])) {
            $userIds = CustomerLifetimeValue::whereIn('value_tier', $criteria['clv_tier'])
                ->pluck('user_id');
            return User::whereIn('id', $userIds)->get();
        }

        return collect([]);
    }

    /**
     * Evaluate custom criteria
     */
    protected function evaluateCustomCriteria($criteria)
    {
        // Custom SQL or complex logic
        return collect([]);
    }

    /**
     * Get segment-specific data for user
     */
    protected function getSegmentDataForUser($user)
    {
        return [
            'assigned_at' => now()->toDateTimeString()
        ];
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('segment_type', $type);
    }
}
