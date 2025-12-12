<?php

namespace App\Services;

use App\Models\StockForecast;
use App\Models\ReorderPoint;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\SupplierPerformance;
use App\Models\StockAlert;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InventoryForecastingService
{
    /**
     * Generate demand forecasts for a product
     */
    public function generateForecast($productId, $days = 30, $algorithm = 'auto')
    {
        $forecasts = [];
        $startDate = Carbon::tomorrow();

        for ($i = 0; $i < $days; $i++) {
            $forecastDate = $startDate->copy()->addDays($i);
            
            // Skip if forecast already exists
            $existing = StockForecast::where('product_id', $productId)
                ->where('forecast_date', $forecastDate)
                ->first();
            
            if ($existing) {
                $forecasts[] = $existing;
                continue;
            }

            // Generate based on algorithm
            $forecast = null;
            switch ($algorithm) {
                case 'moving_average':
                    $forecast = StockForecast::generateMovingAverage($productId, $forecastDate);
                    break;
                case 'exponential_smoothing':
                    $forecast = StockForecast::generateExponentialSmoothing($productId, $forecastDate);
                    break;
                case 'trend_seasonal':
                    $forecast = StockForecast::generateTrendSeasonalForecast($productId, $forecastDate);
                    break;
                case 'auto':
                default:
                    // Choose best algorithm based on data availability
                    $forecast = $this->chooseBestForecast($productId, $forecastDate);
                    break;
            }

            if ($forecast) {
                $forecasts[] = $forecast;
            }
        }

        return $forecasts;
    }

    /**
     * Choose best forecast algorithm based on historical data
     */
    private function chooseBestForecast($productId, $forecastDate)
    {
        // Get historical data points
        $historicalDays = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('order_items.product_id', $productId)
            ->where('orders.status', 'completed')
            ->where('orders.completed_at', '>=', Carbon::now()->subDays(90))
            ->count();

        // Choose algorithm based on data availability
        if ($historicalDays >= 60) {
            // Enough data for trend/seasonal analysis
            return StockForecast::generateTrendSeasonalForecast($productId, $forecastDate);
        } else if ($historicalDays >= 30) {
            // Enough for exponential smoothing
            return StockForecast::generateExponentialSmoothing($productId, $forecastDate);
        } else if ($historicalDays >= 14) {
            // Use simple moving average
            return StockForecast::generateMovingAverage($productId, $forecastDate);
        }

        return null;
    }

    /**
     * Generate forecasts for all products with reorder points
     */
    public function generateAllForecasts($days = 30)
    {
        $products = ReorderPoint::active()->pluck('product_id')->unique();
        $totalForecasts = 0;

        foreach ($products as $productId) {
            try {
                $forecasts = $this->generateForecast($productId, $days);
                $totalForecasts += count($forecasts);
            } catch (\Exception $e) {
                Log::error("Failed to generate forecast for product {$productId}: " . $e->getMessage());
            }
        }

        return $totalForecasts;
    }

    /**
     * Update reorder point from forecasts
     */
    public function updateReorderPointFromForecast($reorderPointId)
    {
        $reorderPoint = ReorderPoint::find($reorderPointId);
        if (!$reorderPoint) {
            return false;
        }

        // Get next 30 days of forecasts
        $forecasts = StockForecast::where('product_id', $reorderPoint->product_id)
            ->where('forecast_date', '>=', Carbon::tomorrow())
            ->where('forecast_date', '<=', Carbon::now()->addDays(30))
            ->where('confidence_level', '>=', 0.6)
            ->get();

        if ($forecasts->isEmpty()) {
            // Fall back to historical data
            return $reorderPoint->updateFromHistory();
        }

        // Calculate average daily demand from forecasts
        $avgDemand = $forecasts->avg('predicted_demand');
        $reorderPoint->avg_daily_demand = $avgDemand;

        // Calculate demand variability
        $demands = $forecasts->pluck('predicted_demand')->toArray();
        $mean = array_sum($demands) / count($demands);
        $variance = array_sum(array_map(function($d) use ($mean) {
            return pow($d - $mean, 2);
        }, $demands)) / count($demands);
        $stdDev = sqrt($variance);
        $reorderPoint->demand_variability = $mean > 0 ? $stdDev / $mean : 0;

        $reorderPoint->save();

        // Recalculate safety stock, reorder level, EOQ
        $reorderPoint->calculateSafetyStock();
        $reorderPoint->calculateReorderLevel();
        $reorderPoint->calculateEOQ();

        return $reorderPoint;
    }

    /**
     * Update all reorder points from forecasts
     */
    public function updateAllReorderPoints()
    {
        $reorderPoints = ReorderPoint::active()->get();
        $updated = 0;

        foreach ($reorderPoints as $rp) {
            try {
                if ($this->updateReorderPointFromForecast($rp->id)) {
                    $updated++;
                }
            } catch (\Exception $e) {
                Log::error("Failed to update reorder point {$rp->id}: " . $e->getMessage());
            }
        }

        return $updated;
    }

    /**
     * Check products that need reordering
     */
    public function checkReorderNeeds()
    {
        $reorderPoints = ReorderPoint::needsTrigger()->with('product', 'supplier')->get();
        $posCreated = [];

        foreach ($reorderPoints as $rp) {
            try {
                // Check if PO already exists for this product/supplier
                $existingPO = PurchaseOrder::where('supplier_id', $rp->supplier_id)
                    ->whereHas('items', function($query) use ($rp) {
                        $query->where('product_id', $rp->product_id);
                    })
                    ->whereIn('status', [
                        PurchaseOrder::STATUS_DRAFT,
                        PurchaseOrder::STATUS_SENT,
                        PurchaseOrder::STATUS_CONFIRMED,
                    ])
                    ->first();

                if ($existingPO) {
                    continue; // Skip if PO already exists
                }

                // Create purchase order
                $po = PurchaseOrder::createFromReorderPoint($rp);
                $posCreated[] = $po;

                // Create alert
                StockAlert::createReorderNeededAlert(
                    $rp->product_id,
                    $rp->product->stock_quantity,
                    $rp->reorder_level
                );

            } catch (\Exception $e) {
                Log::error("Failed to create PO for reorder point {$rp->id}: " . $e->getMessage());
            }
        }

        return $posCreated;
    }

    /**
     * Predict stockout risk for next N days
     */
    public function predictStockoutRisk($productId, $days = 30)
    {
        $product = Product::find($productId);
        if (!$product) {
            return null;
        }

        $currentStock = $product->stock_quantity;

        // Get forecasts for the period
        $forecasts = StockForecast::where('product_id', $productId)
            ->where('forecast_date', '>=', Carbon::tomorrow())
            ->where('forecast_date', '<=', Carbon::now()->addDays($days))
            ->orderBy('forecast_date')
            ->get();

        if ($forecasts->isEmpty()) {
            return null;
        }

        $stockLevel = $currentStock;
        $stockoutDays = [];
        $lowStockDays = [];

        $reorderPoint = ReorderPoint::where('product_id', $productId)->first();
        $safetyStock = $reorderPoint ? $reorderPoint->safety_stock : 0;

        foreach ($forecasts as $forecast) {
            $stockLevel -= $forecast->predicted_demand;

            if ($stockLevel <= 0) {
                $stockoutDays[] = $forecast->forecast_date;
            } else if ($stockLevel <= $safetyStock) {
                $lowStockDays[] = $forecast->forecast_date;
            }
        }

        return [
            'product_id' => $productId,
            'current_stock' => $currentStock,
            'days_analyzed' => $days,
            'stockout_risk' => !empty($stockoutDays),
            'stockout_date' => !empty($stockoutDays) ? $stockoutDays[0] : null,
            'low_stock_dates' => $lowStockDays,
            'risk_level' => $this->calculateRiskLevel($stockoutDays, $lowStockDays, $days),
            'projected_final_stock' => $stockLevel,
        ];
    }

    /**
     * Calculate risk level
     */
    private function calculateRiskLevel($stockoutDays, $lowStockDays, $totalDays)
    {
        if (!empty($stockoutDays)) {
            $daysUntilStockout = Carbon::now()->diffInDays($stockoutDays[0]);
            if ($daysUntilStockout <= 7) return 'critical';
            if ($daysUntilStockout <= 14) return 'high';
            return 'medium';
        }

        if (!empty($lowStockDays)) {
            return 'medium';
        }

        return 'low';
    }

    /**
     * Evaluate supplier performance
     */
    public function evaluateSupplierPerformance($supplierId, $startDate = null, $endDate = null)
    {
        if (!$startDate) {
            $startDate = Carbon::now()->subMonth()->startOfMonth();
        }
        if (!$endDate) {
            $endDate = Carbon::now()->subMonth()->endOfMonth();
        }

        return SupplierPerformance::evaluateSupplier($supplierId, $startDate, $endDate);
    }

    /**
     * Evaluate all suppliers
     */
    public function evaluateAllSuppliers($startDate = null, $endDate = null)
    {
        return SupplierPerformance::evaluateAllSuppliers($startDate, $endDate);
    }

    /**
     * Get recommended supplier for product
     */
    public function recommendSupplier($productId, $quantity)
    {
        return Supplier::recommendForProduct($productId, $quantity);
    }

    /**
     * Generate and check all stock alerts
     */
    public function generateStockAlerts()
    {
        return StockAlert::generateAllAlerts();
    }

    /**
     * Auto-resolve invalid alerts
     */
    public function autoResolveAlerts()
    {
        return StockAlert::autoResolveInvalidAlerts();
    }

    /**
     * Get inventory analytics
     */
    public function getAnalytics($days = 30)
    {
        return [
            'forecasting' => [
                'total_forecasts' => StockForecast::where('forecast_date', '>=', Carbon::now())
                    ->where('forecast_date', '<=', Carbon::now()->addDays($days))
                    ->count(),
                'avg_confidence' => StockForecast::where('forecast_date', '>=', Carbon::now())
                    ->where('forecast_date', '<=', Carbon::now()->addDays($days))
                    ->avg('confidence_level'),
                'accuracy_stats' => StockForecast::getAccuracyStats($days),
            ],
            'reorder_points' => ReorderPoint::getStatistics(),
            'purchase_orders' => PurchaseOrder::getStatistics($days),
            'suppliers' => Supplier::getStatistics(),
            'supplier_performance' => SupplierPerformance::getStatistics(6),
            'stock_alerts' => StockAlert::getStatistics(),
        ];
    }

    /**
     * Get inventory health score (0-100)
     */
    public function getInventoryHealthScore()
    {
        $score = 100;

        // Deduct for critical alerts
        $criticalAlerts = StockAlert::unresolved()->critical()->count();
        $score -= min(30, $criticalAlerts * 10);

        // Deduct for overdue POs
        $overduePOs = PurchaseOrder::overdue()->count();
        $score -= min(20, $overduePOs * 5);

        // Deduct for products needing reorder
        $needsReorder = ReorderPoint::needsTrigger()->count();
        $score -= min(20, $needsReorder * 2);

        // Deduct for poor supplier performance
        $poorSuppliers = SupplierPerformance::recent(3)->poorPerformers()->count();
        $score -= min(15, $poorSuppliers * 5);

        // Deduct for low forecast accuracy
        $accuracy = StockForecast::getAccuracyStats(30)['overall_accuracy'] ?? 70;
        if ($accuracy < 70) {
            $score -= (70 - $accuracy) / 2;
        }

        return max(0, round($score));
    }

    /**
     * Get products at risk of stockout
     */
    public function getStockoutRisks($days = 30, $minRiskLevel = 'medium')
    {
        $products = ReorderPoint::active()->with('product')->get();
        $risks = [];

        foreach ($products as $rp) {
            try {
                $risk = $this->predictStockoutRisk($rp->product_id, $days);
                
                if ($risk && $this->meetsRiskThreshold($risk['risk_level'], $minRiskLevel)) {
                    $risks[] = array_merge($risk, [
                        'product_name' => $rp->product->name,
                        'reorder_level' => $rp->reorder_level,
                        'eoq' => $rp->economic_order_quantity,
                    ]);
                }
            } catch (\Exception $e) {
                Log::error("Failed to calculate stockout risk for product {$rp->product_id}: " . $e->getMessage());
            }
        }

        // Sort by risk level (critical first)
        usort($risks, function($a, $b) {
            $levels = ['critical' => 4, 'high' => 3, 'medium' => 2, 'low' => 1];
            return ($levels[$b['risk_level']] ?? 0) - ($levels[$a['risk_level']] ?? 0);
        });

        return $risks;
    }

    /**
     * Check if risk level meets threshold
     */
    private function meetsRiskThreshold($riskLevel, $threshold)
    {
        $levels = ['low' => 1, 'medium' => 2, 'high' => 3, 'critical' => 4];
        return ($levels[$riskLevel] ?? 0) >= ($levels[$threshold] ?? 0);
    }

    /**
     * Run complete inventory optimization
     */
    public function runCompleteOptimization()
    {
        $results = [];

        try {
            // 1. Generate forecasts for next 30 days
            $results['forecasts_generated'] = $this->generateAllForecasts(30);

            // 2. Update all reorder points
            $results['reorder_points_updated'] = $this->updateAllReorderPoints();

            // 3. Check reorder needs and create POs
            $results['pos_created'] = count($this->checkReorderNeeds());

            // 4. Generate stock alerts
            $results['alerts_generated'] = $this->generateStockAlerts();

            // 5. Auto-resolve invalid alerts
            $results['alerts_resolved'] = $this->autoResolveAlerts();

            // 6. Evaluate suppliers (last month)
            $results['suppliers_evaluated'] = $this->evaluateAllSuppliers();

            // 7. Calculate health score
            $results['health_score'] = $this->getInventoryHealthScore();

            $results['success'] = true;
            $results['timestamp'] = Carbon::now();

        } catch (\Exception $e) {
            $results['success'] = false;
            $results['error'] = $e->getMessage();
            Log::error("Inventory optimization failed: " . $e->getMessage());
        }

        return $results;
    }

    /**
     * Get forecast accuracy report
     */
    public function getForecastAccuracyReport($days = 30)
    {
        return [
            'period_days' => $days,
            'overall_stats' => StockForecast::getAccuracyStats($days),
            'by_product' => StockForecast::withActuals()
                ->where('created_at', '>=', Carbon::now()->subDays($days))
                ->with('product:id,name')
                ->groupBy('product_id')
                ->selectRaw('product_id, AVG(accuracy_percentage) as avg_accuracy, COUNT(*) as forecast_count')
                ->orderBy('avg_accuracy', 'desc')
                ->limit(20)
                ->get(),
            'worst_performers' => StockForecast::withActuals()
                ->where('created_at', '>=', Carbon::now()->subDays($days))
                ->where('accuracy_percentage', '<', 70)
                ->with('product:id,name')
                ->orderBy('accuracy_percentage')
                ->limit(10)
                ->get(),
        ];
    }
}
