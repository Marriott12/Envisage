<?php

namespace App\Services;

use App\Models\AnalyticEvent;
use App\Models\UserSession;
use App\Models\BusinessMetric;
use App\Models\ProductAnalytic;
use App\Models\CohortAnalysis;
use App\Models\UserBehaviorScore;
use App\Models\User;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AnalyticsService
{
    /**
     * Aggregate daily metrics
     */
    public function aggregateDailyMetrics($date)
    {
        try {
            // Revenue metrics
            $revenue = Order::whereDate('created_at', $date)
                ->where('status', 'completed')
                ->sum('total');

            BusinessMetric::recordMetric($date, 'revenue', $revenue, null, null, Order::whereDate('created_at', $date)->where('status', 'completed')->count());

            // Session metrics
            $sessions = UserSession::whereDate('started_at', $date)->count();
            BusinessMetric::recordMetric($date, 'sessions', $sessions);

            // User metrics
            $newUsers = User::whereDate('created_at', $date)->count();
            BusinessMetric::recordMetric($date, 'new_users', $newUsers);

            // Event metrics
            $events = AnalyticEvent::whereDate('created_at', $date)->count();
            BusinessMetric::recordMetric($date, 'events', $events);

            Log::info("Daily metrics aggregated for {$date}");

        } catch (\Exception $e) {
            Log::error("Failed to aggregate daily metrics: " . $e->getMessage());
        }
    }

    /**
     * Calculate product analytics
     */
    public function calculateProductAnalytics($date)
    {
        try {
            $products = DB::table('products')->pluck('id');

            foreach ($products as $productId) {
                $views = AnalyticEvent::productViews()
                    ->whereDate('created_at', $date)
                    ->where('properties->product_id', $productId)
                    ->count();

                $uniqueViews = AnalyticEvent::productViews()
                    ->whereDate('created_at', $date)
                    ->where('properties->product_id', $productId)
                    ->distinct('session_id')
                    ->count('session_id');

                $addToCart = AnalyticEvent::addToCart()
                    ->whereDate('created_at', $date)
                    ->where('properties->product_id', $productId)
                    ->count();

                $purchases = DB::table('order_items')
                    ->join('orders', 'order_items.order_id', '=', 'orders.id')
                    ->where('order_items.product_id', $productId)
                    ->whereDate('orders.created_at', $date)
                    ->where('orders.status', 'completed')
                    ->count();

                $revenue = DB::table('order_items')
                    ->join('orders', 'order_items.order_id', '=', 'orders.id')
                    ->where('order_items.product_id', $productId)
                    ->whereDate('orders.created_at', $date)
                    ->where('orders.status', 'completed')
                    ->sum(DB::raw('order_items.price * order_items.quantity'));

                $conversionRate = $views > 0 ? round(($purchases / $views) * 100, 2) : 0;

                ProductAnalytic::recordAnalytics($date, $productId, [
                    'views' => $views,
                    'unique_views' => $uniqueViews,
                    'add_to_cart' => $addToCart,
                    'purchases' => $purchases,
                    'revenue' => $revenue,
                    'conversion_rate' => $conversionRate,
                ]);
            }

            Log::info("Product analytics calculated for {$date}");

        } catch (\Exception $e) {
            Log::error("Failed to calculate product analytics: " . $e->getMessage());
        }
    }

    /**
     * Calculate cohort retention
     */
    public function calculateCohortRetention($cohortDate, $cohortType = 'registration', $periodType = 'week')
    {
        try {
            // Get users in cohort
            $cohortUsers = User::whereDate('created_at', $cohortDate)->pluck('id');
            $cohortSize = $cohortUsers->count();

            if ($cohortSize === 0) {
                return;
            }

            // Calculate retention for each period
            $maxPeriods = 12; // Track up to 12 periods

            for ($period = 0; $period <= $maxPeriods; $period++) {
                switch ($periodType) {
                    case 'day':
                        $periodStart = now()->parse($cohortDate)->addDays($period)->startOfDay();
                        break;
                    case 'month':
                        $periodStart = now()->parse($cohortDate)->addMonths($period)->startOfMonth();
                        break;
                    case 'week':
                    default:
                        $periodStart = now()->parse($cohortDate)->addWeeks($period)->startOfWeek();
                        break;
                }

                switch ($periodType) {
                    case 'day':
                        $periodEnd = $periodStart->copy()->endOfDay();
                        break;
                    case 'month':
                        $periodEnd = $periodStart->copy()->endOfMonth();
                        break;
                    case 'week':
                    default:
                        $periodEnd = $periodStart->copy()->endOfWeek();
                        break;
                }

                // Count active users in this period
                $retainedUsers = UserSession::whereIn('user_id', $cohortUsers)
                    ->whereBetween('started_at', [$periodStart, $periodEnd])
                    ->distinct('user_id')
                    ->count('user_id');

                // Calculate revenue from cohort in this period
                $revenue = Order::whereIn('user_id', $cohortUsers)
                    ->whereBetween('created_at', [$periodStart, $periodEnd])
                    ->where('status', 'completed')
                    ->sum('total');

                CohortAnalysis::recordCohort(
                    $cohortDate,
                    $cohortType,
                    $cohortSize,
                    $period,
                    $periodType,
                    $retainedUsers,
                    $revenue
                );
            }

            Log::info("Cohort retention calculated for {$cohortDate}");

        } catch (\Exception $e) {
            Log::error("Failed to calculate cohort retention: " . $e->getMessage());
        }
    }

    /**
     * Calculate user behavior scores
     */
    public function calculateUserBehaviorScores($userId)
    {
        try {
            $user = User::find($userId);

            if (!$user) {
                return;
            }

            // Calculate engagement score (0-100)
            $recentSessions = UserSession::where('user_id', $userId)
                ->where('started_at', '>=', now()->subDays(30))
                ->count();

            $recentEvents = AnalyticEvent::where('user_id', $userId)
                ->where('created_at', '>=', now()->subDays(30))
                ->count();

            $engagementScore = min(100, ($recentSessions * 5) + ($recentEvents * 0.5));

            // Calculate purchase propensity (0-100)
            $orderCount = Order::where('user_id', $userId)->count();
            $cartAdds = AnalyticEvent::addToCart()
                ->where('user_id', $userId)
                ->where('created_at', '>=', now()->subDays(30))
                ->count();

            $purchasePropensity = min(100, ($orderCount * 10) + ($cartAdds * 2));

            // Calculate churn risk (0-100)
            $daysSinceLastSession = UserSession::where('user_id', $userId)
                ->latest('started_at')
                ->value('started_at');

            $churnRisk = $daysSinceLastSession 
                ? min(100, now()->diffInDays($daysSinceLastSession) * 3)
                : 50;

            // Calculate lifetime value prediction
            $totalSpent = Order::where('user_id', $userId)
                ->where('status', 'completed')
                ->sum('total');

            $avgOrderValue = $orderCount > 0 ? $totalSpent / $orderCount : 0;
            $ltv = $avgOrderValue * (12 - ($churnRisk / 10)); // Estimate 12 months, adjusted for churn

            // Get preferences
            $topCategories = DB::table('order_items')
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->join('products', 'order_items.product_id', '=', 'products.id')
                ->where('orders.user_id', $userId)
                ->selectRaw('products.category_id, COUNT(*) as count')
                ->groupBy('products.category_id')
                ->orderByDesc('count')
                ->limit(5)
                ->pluck('category_id')
                ->toArray();

            $scores = [
                'engagement_score' => round($engagementScore, 2),
                'purchase_propensity' => round($purchasePropensity, 2),
                'churn_risk' => round($churnRisk, 2),
                'lifetime_value_prediction' => round($ltv, 2),
                'preferences' => ['top_categories' => $topCategories],
            ];

            UserBehaviorScore::updateOrCreate(
                ['user_id' => $userId],
                array_merge($scores, ['last_calculated_at' => now()])
            );

            // Determine segment
            $behaviorScore = UserBehaviorScore::where('user_id', $userId)->first();
            if ($behaviorScore) {
                $behaviorScore->recalculateSegment();
            }

            Log::info("User behavior scores calculated for user {$userId}");

        } catch (\Exception $e) {
            Log::error("Failed to calculate user behavior scores: " . $e->getMessage());
        }
    }

    /**
     * Get conversion funnel data
     */
    public function getConversionFunnelData($funnelId, $startDate, $endDate)
    {
        $funnel = \App\Models\ConversionFunnel::findOrFail($funnelId);

        $data = [
            'funnel' => $funnel,
            'overall_conversion_rate' => $funnel->getConversionRate(),
            'drop_off_rates' => $funnel->getDropOffRates(),
            'avg_time_per_step' => $funnel->getAverageTimePerStep(),
        ];

        return $data;
    }
}
