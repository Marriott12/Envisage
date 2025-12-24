<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Predictive Analytics & Forecasting Service
 * 
 * Features:
 * - Time series forecasting (ARIMA, Prophet, LSTM)
 * - Demand prediction
 * - Churn prediction
 * - Customer Lifetime Value (CLV) prediction
 * - Sales forecasting
 * - Trend detection
 * - Automated insights generation
 */
class PredictiveAnalyticsService
{
    protected $mlServiceUrl;

    public function __construct()
    {
        $this->mlServiceUrl = config('services.ml.url', env('ML_SERVICE_URL', 'http://localhost:5000'));
    }

    /**
     * Forecast product demand using time series models
     */
    public function forecastDemand($productId, $days = 30)
    {
        $cacheKey = "demand_forecast:{$productId}:{$days}";

        return Cache::remember($cacheKey, 3600, function () use ($productId, $days) {
            // Get historical sales data
            $historicalData = $this->getHistoricalSales($productId);

            if (count($historicalData) < 30) {
                return null; // Not enough data
            }

            try {
                $response = Http::timeout(15)->post("{$this->mlServiceUrl}/api/forecast/demand", [
                    'product_id' => $productId,
                    'historical_data' => $historicalData,
                    'forecast_days' => $days,
                    'model' => 'prophet', // Facebook Prophet
                ]);

                if ($response->successful()) {
                    return $response->json()['forecast'];
                }
            } catch (\Exception $e) {
                \Log::warning("Demand forecasting failed: " . $e->getMessage());
            }

            // Fallback to moving average
            return $this->movingAverageForecast($historicalData, $days);
        });
    }

    /**
     * Predict customer churn probability
     */
    public function predictChurn($userId)
    {
        $cacheKey = "churn_prediction:{$userId}";

        return Cache::remember($cacheKey, 86400, function () use ($userId) {
            $features = $this->extractChurnFeatures($userId);

            try {
                $response = Http::timeout(5)->post("{$this->mlServiceUrl}/api/predict/churn", [
                    'user_id' => $userId,
                    'features' => $features,
                    'model' => 'xgboost_churn',
                ]);

                if ($response->successful()) {
                    return $response->json();
                }
            } catch (\Exception $e) {
                \Log::warning("Churn prediction failed: " . $e->getMessage());
            }

            // Fallback to rule-based prediction
            return $this->ruleBasedChurnPrediction($features);
        });
    }

    /**
     * Predict Customer Lifetime Value
     */
    public function predictCLV($userId)
    {
        $cacheKey = "clv_prediction:{$userId}";

        return Cache::remember($cacheKey, 86400, function () use ($userId) {
            $features = $this->extractCLVFeatures($userId);

            try {
                $response = Http::timeout(5)->post("{$this->mlServiceUrl}/api/predict/clv", [
                    'user_id' => $userId,
                    'features' => $features,
                    'model' => 'gamma_gamma_clv',
                ]);

                if ($response->successful()) {
                    return $response->json()['predicted_clv'];
                }
            } catch (\Exception $e) {
                \Log::warning("CLV prediction failed: " . $e->getMessage());
            }

            // Fallback calculation
            return $this->simpleCLVCalculation($userId);
        });
    }

    /**
     * Forecast overall sales
     */
    public function forecastSales($days = 30, $granularity = 'daily')
    {
        $cacheKey = "sales_forecast:{$days}:{$granularity}";

        return Cache::remember($cacheKey, 3600, function () use ($days, $granularity) {
            $historicalData = $this->getHistoricalRevenue($granularity);

            try {
                $response = Http::timeout(15)->post("{$this->mlServiceUrl}/api/forecast/sales", [
                    'historical_data' => $historicalData,
                    'forecast_days' => $days,
                    'granularity' => $granularity,
                    'model' => 'lstm', // Long Short-Term Memory
                ]);

                if ($response->successful()) {
                    return $response->json()['forecast'];
                }
            } catch (\Exception $e) {
                \Log::warning("Sales forecasting failed: " . $e->getMessage());
            }

            return $this->exponentialSmoothingForecast($historicalData, $days);
        });
    }

    /**
     * Detect trending products
     */
    public function detectTrendingProducts($limit = 20)
    {
        $cacheKey = "trending_detection";

        return Cache::remember($cacheKey, 1800, function () use ($limit) {
            try {
                $response = Http::timeout(10)->post("{$this->mlServiceUrl}/api/detect/trends", [
                    'limit' => $limit,
                    'time_window' => '7days',
                ]);

                if ($response->successful()) {
                    $productIds = $response->json()['trending_ids'];
                    return Product::whereIn('id', $productIds)->get();
                }
            } catch (\Exception $e) {
                \Log::warning("Trend detection failed: " . $e->getMessage());
            }

            // Fallback: momentum-based trending
            return $this->momentumBasedTrending($limit);
        });
    }

    /**
     * Predict next purchase time
     */
    public function predictNextPurchase($userId)
    {
        $cacheKey = "next_purchase:{$userId}";

        return Cache::remember($cacheKey, 86400, function () use ($userId) {
            $purchaseHistory = Order::where('user_id', $userId)
                ->where('status', 'completed')
                ->orderBy('created_at')
                ->pluck('created_at')
                ->toArray();

            if (count($purchaseHistory) < 2) {
                return null;
            }

            try {
                $response = Http::timeout(5)->post("{$this->mlServiceUrl}/api/predict/next-purchase", [
                    'user_id' => $userId,
                    'purchase_history' => $purchaseHistory,
                ]);

                if ($response->successful()) {
                    return $response->json()['predicted_date'];
                }
            } catch (\Exception $e) {
                \Log::warning("Next purchase prediction failed: " . $e->getMessage());
            }

            // Fallback: average time between purchases
            return $this->averageInterPurchaseTime($purchaseHistory);
        });
    }

    /**
     * Generate automated insights
     */
    public function generateInsights($timeframe = '7days')
    {
        $insights = [];

        // Sales insights
        $salesChange = $this->analyzeSalesChange($timeframe);
        if (abs($salesChange) > 10) {
            $direction = $salesChange > 0 ? 'increased' : 'decreased';
            $insights[] = [
                'type' => 'sales',
                'severity' => abs($salesChange) > 20 ? 'high' : 'medium',
                'message' => "Sales have {$direction} by " . number_format(abs($salesChange), 1) . "% compared to previous period.",
                'action' => $salesChange < 0 ? 'Consider running a promotion' : 'Maintain current strategy',
            ];
        }

        // Inventory insights
        $lowStock = $this->detectLowStockProducts();
        if ($lowStock->count() > 0) {
            $insights[] = [
                'type' => 'inventory',
                'severity' => 'high',
                'message' => "{$lowStock->count()} products are running low on stock.",
                'action' => 'Reorder inventory for: ' . $lowStock->pluck('name')->take(3)->implode(', '),
                'products' => $lowStock->take(5),
            ];
        }

        // Customer behavior insights
        $churnRisk = $this->detectChurnRiskCustomers();
        if ($churnRisk->count() > 0) {
            $insights[] = [
                'type' => 'retention',
                'severity' => 'medium',
                'message' => "{$churnRisk->count()} customers are at risk of churning.",
                'action' => 'Send re-engagement campaign to at-risk customers',
            ];
        }

        // Product performance insights
        $underperforming = $this->detectUnderperformingProducts();
        if ($underperforming->count() > 0) {
            $insights[] = [
                'type' => 'product_performance',
                'severity' => 'medium',
                'message' => "{$underperforming->count()} products are underperforming.",
                'action' => 'Review pricing and marketing for these products',
                'products' => $underperforming->take(5),
            ];
        }

        return $insights;
    }

    /**
     * Predict product return probability
     */
    public function predictReturnProbability($orderId)
    {
        $order = Order::with('items')->find($orderId);

        if (!$order) {
            return null;
        }

        try {
            $response = Http::timeout(5)->post("{$this->mlServiceUrl}/api/predict/return", [
                'order_id' => $orderId,
                'features' => $this->extractReturnFeatures($order),
            ]);

            if ($response->successful()) {
                return $response->json()['return_probability'];
            }
        } catch (\Exception $e) {
            \Log::warning("Return prediction failed: " . $e->getMessage());
        }

        return null;
    }

    // Helper methods

    protected function getHistoricalSales($productId, $days = 90)
    {
        return DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('order_items.product_id', $productId)
            ->where('orders.created_at', '>=', now()->subDays($days))
            ->selectRaw('DATE(orders.created_at) as date, SUM(order_items.quantity) as quantity')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(function ($item) {
                return [
                    'date' => $item->date,
                    'value' => $item->quantity,
                ];
            })
            ->toArray();
    }

    protected function getHistoricalRevenue($granularity = 'daily')
    {
        $dateFormat = $granularity === 'hourly' ? '%Y-%m-%d %H:00:00' : '%Y-%m-%d';

        return Order::selectRaw("DATE_FORMAT(created_at, '{$dateFormat}') as date, SUM(total_amount) as revenue")
            ->where('status', 'completed')
            ->where('created_at', '>=', now()->subDays(90))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(function ($item) {
                return [
                    'date' => $item->date,
                    'value' => $item->revenue,
                ];
            })
            ->toArray();
    }

    protected function extractChurnFeatures($userId)
    {
        $user = User::find($userId);
        $orders = Order::where('user_id', $userId)->get();

        return [
            'days_since_last_order' => $this->getDaysSinceLastOrder($userId),
            'total_orders' => $orders->count(),
            'avg_order_value' => $orders->avg('total_amount') ?? 0,
            'account_age_days' => $user->created_at->diffInDays(now()),
            'email_engagement_rate' => $this->getEmailEngagementRate($userId),
            'support_tickets_count' => $this->getSupportTicketsCount($userId),
            'avg_days_between_orders' => $this->getAvgDaysBetweenOrders($userId),
        ];
    }

    protected function extractCLVFeatures($userId)
    {
        return [
            'total_orders' => Order::where('user_id', $userId)->count(),
            'total_spent' => Order::where('user_id', $userId)->sum('total_amount'),
            'avg_order_value' => Order::where('user_id', $userId)->avg('total_amount') ?? 0,
            'purchase_frequency' => $this->getPurchaseFrequency($userId),
            'account_age_days' => User::find($userId)->created_at->diffInDays(now()),
        ];
    }

    protected function extractReturnFeatures($order)
    {
        return [
            'order_value' => $order->total_amount,
            'item_count' => $order->items->count(),
            'category_return_rate' => $this->getCategoryReturnRate($order),
            'customer_return_history' => $this->getCustomerReturnRate($order->user_id),
        ];
    }

    protected function ruleBasedChurnPrediction($features)
    {
        $churnScore = 0.0;

        if ($features['days_since_last_order'] > 90) {
            $churnScore += 0.4;
        }
        if ($features['email_engagement_rate'] < 0.1) {
            $churnScore += 0.3;
        }
        if ($features['support_tickets_count'] > 5) {
            $churnScore += 0.2;
        }

        return [
            'churn_probability' => min($churnScore, 1.0),
            'is_at_risk' => $churnScore > 0.5,
        ];
    }

    protected function simpleCLVCalculation($userId)
    {
        $totalSpent = Order::where('user_id', $userId)->sum('total_amount');
        $orderCount = Order::where('user_id', $userId)->count();
        $avgOrderValue = $orderCount > 0 ? $totalSpent / $orderCount : 0;

        // Simple CLV = Avg Order Value * Purchase Frequency * Customer Lifespan
        $purchaseFrequency = $this->getPurchaseFrequency($userId);
        $estimatedLifespan = 3; // years

        return $avgOrderValue * $purchaseFrequency * 12 * $estimatedLifespan;
    }

    protected function movingAverageForecast($historicalData, $days)
    {
        $window = min(7, count($historicalData));
        $recentValues = array_slice(array_column($historicalData, 'value'), -$window);
        $avg = array_sum($recentValues) / count($recentValues);

        $forecast = [];
        for ($i = 1; $i <= $days; $i++) {
            $forecast[] = [
                'date' => now()->addDays($i)->format('Y-m-d'),
                'predicted_value' => $avg,
            ];
        }

        return $forecast;
    }

    protected function exponentialSmoothingForecast($historicalData, $days)
    {
        $alpha = 0.3; // Smoothing factor
        $values = array_column($historicalData, 'value');
        
        $smoothed = $values[0];
        foreach ($values as $value) {
            $smoothed = $alpha * $value + (1 - $alpha) * $smoothed;
        }

        $forecast = [];
        for ($i = 1; $i <= $days; $i++) {
            $forecast[] = [
                'date' => now()->addDays($i)->format('Y-m-d'),
                'predicted_value' => $smoothed,
            ];
        }

        return $forecast;
    }

    protected function momentumBasedTrending($limit)
    {
        $recentSales = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.created_at', '>=', now()->subDays(7))
            ->selectRaw('product_id, COUNT(*) as recent_count')
            ->groupBy('product_id')
            ->pluck('recent_count', 'product_id');

        $olderSales = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->whereBetween('orders.created_at', [now()->subDays(14), now()->subDays(7)])
            ->selectRaw('product_id, COUNT(*) as older_count')
            ->groupBy('product_id')
            ->pluck('older_count', 'product_id');

        $momentum = [];
        foreach ($recentSales as $productId => $recentCount) {
            $olderCount = $olderSales[$productId] ?? 1;
            $momentum[$productId] = ($recentCount - $olderCount) / $olderCount;
        }

        arsort($momentum);
        $topProductIds = array_slice(array_keys($momentum), 0, $limit);

        return Product::whereIn('id', $topProductIds)->get();
    }

    protected function analyzeSalesChange($timeframe)
    {
        $days = $this->timeframeToDays($timeframe);
        
        $currentPeriod = Order::where('created_at', '>=', now()->subDays($days))
            ->sum('total_amount');

        $previousPeriod = Order::whereBetween('created_at', [
            now()->subDays($days * 2),
            now()->subDays($days)
        ])->sum('total_amount');

        if ($previousPeriod == 0) {
            return 0;
        }

        return (($currentPeriod - $previousPeriod) / $previousPeriod) * 100;
    }

    protected function detectLowStockProducts()
    {
        return Product::whereRaw('stock_quantity < reorder_point')
            ->orWhere('stock_quantity', '<', 10)
            ->get();
    }

    protected function detectChurnRiskCustomers()
    {
        return User::whereHas('orders', function ($query) {
            $query->where('created_at', '<', now()->subDays(60));
        }, '>=', 1)
        ->whereDoesntHave('orders', function ($query) {
            $query->where('created_at', '>=', now()->subDays(60));
        })
        ->limit(50)
        ->get();
    }

    protected function detectUnderperformingProducts()
    {
        return Product::whereRaw('(SELECT COUNT(*) FROM order_items WHERE product_id = products.id AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)) < 5')
            ->where('created_at', '<', now()->subMonths(3))
            ->limit(20)
            ->get();
    }

    protected function getDaysSinceLastOrder($userId)
    {
        $lastOrder = Order::where('user_id', $userId)
            ->orderByDesc('created_at')
            ->first();

        return $lastOrder ? $lastOrder->created_at->diffInDays(now()) : 999;
    }

    protected function getAvgDaysBetweenOrders($userId)
    {
        $orders = Order::where('user_id', $userId)
            ->orderBy('created_at')
            ->pluck('created_at')
            ->toArray();

        if (count($orders) < 2) {
            return 999;
        }

        $intervals = [];
        for ($i = 1; $i < count($orders); $i++) {
            $intervals[] = (strtotime($orders[$i]) - strtotime($orders[$i - 1])) / 86400;
        }

        return array_sum($intervals) / count($intervals);
    }

    protected function getPurchaseFrequency($userId)
    {
        $firstOrder = Order::where('user_id', $userId)
            ->orderBy('created_at')
            ->first();

        if (!$firstOrder) {
            return 0;
        }

        $monthsSinceFirst = max($firstOrder->created_at->diffInMonths(now()), 1);
        $totalOrders = Order::where('user_id', $userId)->count();

        return $totalOrders / $monthsSinceFirst;
    }

    protected function getEmailEngagementRate($userId)
    {
        // Simplified - would integrate with email service
        return 0.5;
    }

    protected function getSupportTicketsCount($userId)
    {
        // Simplified - would query support system
        return 0;
    }

    protected function getCategoryReturnRate($order)
    {
        return 0.05; // 5% average
    }

    protected function getCustomerReturnRate($userId)
    {
        return 0.03; // 3% average
    }

    protected function averageInterPurchaseTime($purchaseHistory)
    {
        $intervals = [];
        for ($i = 1; $i < count($purchaseHistory); $i++) {
            $intervals[] = strtotime($purchaseHistory[$i]) - strtotime($purchaseHistory[$i - 1]);
        }

        if (empty($intervals)) {
            return null;
        }

        $avgInterval = array_sum($intervals) / count($intervals);
        $lastPurchase = end($purchaseHistory);

        return date('Y-m-d', strtotime($lastPurchase) + $avgInterval);
    }

    protected function timeframeToDays($timeframe)
    {
        preg_match('/(\d+)(day|week|month)/', $timeframe, $matches);
        $value = $matches[1] ?? 7;
        $unit = $matches[2] ?? 'day';

        switch ($unit) {
            case 'week':
                return $value * 7;
            case 'month':
                return $value * 30;
            default:
                return $value;
        }
    }
}
