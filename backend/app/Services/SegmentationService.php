<?php

namespace App\Services;

use App\Models\User;
use App\Models\Order;
use App\Models\CustomerSegment;
use App\Models\CustomerSegmentMembership;
use App\Models\RfmScore;
use App\Models\ChurnPrediction;
use App\Models\CustomerLifetimeValue;
use App\Models\NextPurchasePrediction;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class SegmentationService
{
    /**
     * Calculate RFM scores for all customers
     */
    public function calculateAllRfmScores()
    {
        $users = User::whereHas('orders', function($q) {
            $q->where('status', 'completed');
        })->get();

        $calculated = 0;
        foreach ($users as $user) {
            try {
                RfmScore::calculateForUser($user->id);
                $calculated++;
            } catch (\Exception $e) {
                Log::error('Error calculating RFM for user', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $calculated;
    }

    /**
     * Predict churn for all customers
     */
    public function predictAllChurn()
    {
        $users = User::whereHas('orders', function($q) {
            $q->where('status', 'completed');
        })->get();

        $predicted = 0;
        foreach ($users as $user) {
            try {
                ChurnPrediction::predictForUser($user->id);
                $predicted++;
            } catch (\Exception $e) {
                Log::error('Error predicting churn for user', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $predicted;
    }

    /**
     * Calculate CLV for all customers
     */
    public function calculateAllClv()
    {
        $users = User::whereHas('orders', function($q) {
            $q->where('status', 'completed');
        })->get();

        $calculated = 0;
        foreach ($users as $user) {
            try {
                CustomerLifetimeValue::calculateForUser($user->id);
                $calculated++;
            } catch (\Exception $e) {
                Log::error('Error calculating CLV for user', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $calculated;
    }

    /**
     * Predict next purchases for all customers
     */
    public function predictAllNextPurchases()
    {
        $users = User::whereHas('orders', function($q) {
            $q->where('status', 'completed');
        })->having(DB::raw('COUNT(*)'), '>=', 2)->get();

        $predicted = 0;
        foreach ($users as $user) {
            try {
                NextPurchasePrediction::predictForUser($user->id);
                $predicted++;
            } catch (\Exception $e) {
                Log::error('Error predicting next purchase for user', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $predicted;
    }

    /**
     * Recalculate all segment memberships
     */
    public function recalculateAllSegments()
    {
        $segments = CustomerSegment::active()->get();

        $results = [];
        foreach ($segments as $segment) {
            try {
                $count = $segment->calculateMembership();
                $results[$segment->name] = $count;
            } catch (\Exception $e) {
                Log::error('Error calculating segment membership', [
                    'segment_id' => $segment->id,
                    'error' => $e->getMessage()
                ]);
                $results[$segment->name] = 0;
            }
        }

        return $results;
    }

    /**
     * Create default RFM segments
     */
    public function createDefaultRfmSegments()
    {
        $segments = [
            [
                'name' => 'Champions',
                'description' => 'Best customers - recent, frequent, high spending',
                'segment_type' => 'rfm',
                'criteria' => [
                    'rfm_segments' => ['Champions']
                ]
            ],
            [
                'name' => 'Loyal Customers',
                'description' => 'Frequent buyers with good value',
                'segment_type' => 'rfm',
                'criteria' => [
                    'rfm_segments' => ['Loyal Customers']
                ]
            ],
            [
                'name' => 'At Risk',
                'description' => 'Good customers who are disengaging',
                'segment_type' => 'rfm',
                'criteria' => [
                    'rfm_segments' => ['At Risk', 'Cannot Lose Them']
                ]
            ],
            [
                'name' => 'Lost Customers',
                'description' => 'Customers who have churned',
                'segment_type' => 'rfm',
                'criteria' => [
                    'rfm_segments' => ['Lost', 'Hibernating']
                ]
            ],
            [
                'name' => 'New Customers',
                'description' => 'Recently acquired customers',
                'segment_type' => 'rfm',
                'criteria' => [
                    'rfm_segments' => ['New Customers', 'Promising']
                ]
            ]
        ];

        $created = [];
        foreach ($segments as $segmentData) {
            $segment = CustomerSegment::firstOrCreate(
                ['name' => $segmentData['name']],
                $segmentData
            );
            $created[] = $segment;
        }

        return $created;
    }

    /**
     * Create churn risk segments
     */
    public function createChurnRiskSegments()
    {
        $segments = [
            [
                'name' => 'High Churn Risk',
                'description' => 'Customers at high risk of churning',
                'segment_type' => 'predictive',
                'criteria' => [
                    'churn_risk' => ['high', 'critical']
                ]
            ],
            [
                'name' => 'Medium Churn Risk',
                'description' => 'Customers at medium risk of churning',
                'segment_type' => 'predictive',
                'criteria' => [
                    'churn_risk' => ['medium']
                ]
            ]
        ];

        $created = [];
        foreach ($segments as $segmentData) {
            $segment = CustomerSegment::firstOrCreate(
                ['name' => $segmentData['name']],
                $segmentData
            );
            $created[] = $segment;
        }

        return $created;
    }

    /**
     * Create CLV-based segments
     */
    public function createClvSegments()
    {
        $segments = [
            [
                'name' => 'VIP Customers',
                'description' => 'Highest lifetime value customers',
                'segment_type' => 'predictive',
                'criteria' => [
                    'clv_tier' => ['vip', 'platinum']
                ]
            ],
            [
                'name' => 'High Value Customers',
                'description' => 'High lifetime value customers',
                'segment_type' => 'predictive',
                'criteria' => [
                    'clv_tier' => ['gold']
                ]
            ],
            [
                'name' => 'Growing Customers',
                'description' => 'Customers with increasing value',
                'segment_type' => 'predictive',
                'criteria' => [
                    'clv_tier' => ['silver', 'bronze']
                ]
            ]
        ];

        $created = [];
        foreach ($segments as $segmentData) {
            $segment = CustomerSegment::firstOrCreate(
                ['name' => $segmentData['name']],
                $segmentData
            );
            $created[] = $segment;
        }

        return $created;
    }

    /**
     * Get comprehensive customer profile
     */
    public function getCustomerProfile($userId)
    {
        $user = User::findOrFail($userId);

        return [
            'user' => $user,
            'rfm_score' => RfmScore::where('user_id', $userId)->first(),
            'churn_prediction' => ChurnPrediction::where('user_id', $userId)->first(),
            'lifetime_value' => CustomerLifetimeValue::where('user_id', $userId)->first(),
            'next_purchase' => NextPurchasePrediction::where('user_id', $userId)
                ->orderBy('predicted_at', 'desc')
                ->first(),
            'segments' => CustomerSegmentMembership::where('user_id', $userId)
                ->with('segment')
                ->get()
        ];
    }

    /**
     * Get segmentation analytics
     */
    public function getAnalytics()
    {
        return [
            'rfm' => [
                'total_scored' => RfmScore::count(),
                'segment_distribution' => RfmScore::getSegmentDistribution(),
                'avg_monetary_value' => RfmScore::avg('monetary_value'),
                'champions_count' => RfmScore::champions()->count()
            ],
            'churn' => [
                'total_predictions' => ChurnPrediction::count(),
                'high_risk_count' => ChurnPrediction::highRisk()->count(),
                'needs_intervention' => ChurnPrediction::needsIntervention()->count(),
                'avg_churn_probability' => ChurnPrediction::avg('churn_probability')
            ],
            'clv' => CustomerLifetimeValue::getStatistics(),
            'next_purchase' => [
                'total_predictions' => NextPurchasePrediction::count(),
                'upcoming_this_week' => NextPurchasePrediction::upcoming(7)->count(),
                'high_confidence_upcoming' => NextPurchasePrediction::upcoming(7)->highConfidence()->count(),
                'accuracy_stats' => NextPurchasePrediction::getAccuracyStats()
            ],
            'segments' => [
                'total_segments' => CustomerSegment::active()->count(),
                'total_memberships' => CustomerSegmentMembership::count(),
                'by_type' => CustomerSegment::active()
                    ->selectRaw('segment_type, COUNT(*) as count')
                    ->groupBy('segment_type')
                    ->pluck('count', 'segment_type')
            ]
        ];
    }

    /**
     * Trigger churn interventions
     */
    public function triggerChurnInterventions()
    {
        $highRiskCustomers = ChurnPrediction::needsIntervention()->get();

        $triggered = 0;
        foreach ($highRiskCustomers as $prediction) {
            try {
                $prediction->triggerIntervention('email');
                $triggered++;

                // TODO: Actually send intervention email/notification
                Log::info('Churn intervention triggered', [
                    'user_id' => $prediction->user_id,
                    'churn_probability' => $prediction->churn_probability
                ]);
            } catch (\Exception $e) {
                Log::error('Error triggering churn intervention', [
                    'prediction_id' => $prediction->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $triggered;
    }

    /**
     * Send next purchase notifications
     */
    public function sendNextPurchaseNotifications($daysBeforeDate = 3)
    {
        $predictions = NextPurchasePrediction::getDueForNotification($daysBeforeDate);

        $sent = 0;
        foreach ($predictions as $prediction) {
            try {
                $prediction->markNotificationSent();
                $sent++;

                // TODO: Actually send notification
                Log::info('Next purchase notification sent', [
                    'user_id' => $prediction->user_id,
                    'predicted_date' => $prediction->predicted_date
                ]);
            } catch (\Exception $e) {
                Log::error('Error sending next purchase notification', [
                    'prediction_id' => $prediction->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $sent;
    }

    /**
     * Complete segmentation workflow for a user
     */
    public function completeUserAnalysis($userId)
    {
        $results = [];

        try {
            $results['rfm'] = RfmScore::calculateForUser($userId);
        } catch (\Exception $e) {
            $results['rfm_error'] = $e->getMessage();
        }

        try {
            $results['clv'] = CustomerLifetimeValue::calculateForUser($userId);
        } catch (\Exception $e) {
            $results['clv_error'] = $e->getMessage();
        }

        try {
            $results['churn'] = ChurnPrediction::predictForUser($userId);
        } catch (\Exception $e) {
            $results['churn_error'] = $e->getMessage();
        }

        try {
            $results['next_purchase'] = NextPurchasePrediction::predictForUser($userId);
        } catch (\Exception $e) {
            $results['next_purchase_error'] = $e->getMessage();
        }

        return $results;
    }

    /**
     * Run complete segmentation for all customers
     */
    public function runCompleteSegmentation()
    {
        $results = [
            'rfm_scores' => $this->calculateAllRfmScores(),
            'clv_calculations' => $this->calculateAllClv(),
            'churn_predictions' => $this->predictAllChurn(),
            'next_purchases' => $this->predictAllNextPurchases(),
            'segment_memberships' => $this->recalculateAllSegments()
        ];

        Log::info('Complete segmentation run', $results);

        return $results;
    }
}
