<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DemandForecast extends Model
{
    protected $fillable = [
        'product_id',
        'forecast_date',
        'predicted_demand',
        'confidence_score',
        'actual_sales',
        'demand_level',
        'factors',
        'recommended_price',
        'calculated_at',
    ];

    protected $casts = [
        'forecast_date' => 'date',
        'predicted_demand' => 'integer',
        'confidence_score' => 'decimal:4',
        'actual_sales' => 'integer',
        'factors' => 'array',
        'recommended_price' => 'decimal:2',
        'calculated_at' => 'datetime',
    ];

    // Demand levels
    const LEVEL_LOW = 'low';
    const LEVEL_NORMAL = 'normal';
    const LEVEL_HIGH = 'high';
    const LEVEL_SURGE = 'surge';

    /**
     * Relationships
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Scopes
     */
    public function scopeForProduct($query, $productId)
    {
        return $query->where('product_id', $productId);
    }

    public function scopeByDemandLevel($query, $level)
    {
        return $query->where('demand_level', $level);
    }

    public function scopeFuture($query)
    {
        return $query->where('forecast_date', '>', now());
    }

    public function scopePast($query)
    {
        return $query->where('forecast_date', '<=', now());
    }

    public function scopeHighConfidence($query, $threshold = 0.7)
    {
        return $query->where('confidence_score', '>=', $threshold);
    }

    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('forecast_date', [$startDate, $endDate]);
    }

    /**
     * Helper Methods
     */
    public static function createForecast($productId, $forecastDate, $predictedDemand, $confidenceScore, $factors = [], $recommendedPrice = null)
    {
        // Determine demand level
        $demandLevel = static::determineDemandLevel($predictedDemand);

        return static::updateOrCreate(
            [
                'product_id' => $productId,
                'forecast_date' => $forecastDate,
            ],
            [
                'predicted_demand' => $predictedDemand,
                'confidence_score' => round($confidenceScore, 4),
                'demand_level' => $demandLevel,
                'factors' => $factors,
                'recommended_price' => $recommendedPrice,
                'calculated_at' => now(),
            ]
        );
    }

    protected static function determineDemandLevel($predictedDemand)
    {
        if ($predictedDemand < 10) {
            return self::LEVEL_LOW;
        } elseif ($predictedDemand < 50) {
            return self::LEVEL_NORMAL;
        } elseif ($predictedDemand < 100) {
            return self::LEVEL_HIGH;
        } else {
            return self::LEVEL_SURGE;
        }
    }

    public function recordActualSales($actualSales)
    {
        $this->update(['actual_sales' => $actualSales]);
        return $this->calculateAccuracy();
    }

    public function calculateAccuracy()
    {
        if (!$this->actual_sales || !$this->predicted_demand) {
            return null;
        }

        $error = abs($this->actual_sales - $this->predicted_demand);
        $accuracy = max(0, 1 - ($error / max($this->actual_sales, $this->predicted_demand)));

        return round($accuracy * 100, 2); // Return as percentage
    }

    public function isAccurate($threshold = 0.8)
    {
        $accuracy = $this->calculateAccuracy();
        return $accuracy !== null && ($accuracy / 100) >= $threshold;
    }

    /**
     * Get forecast accuracy statistics for a product
     */
    public static function getAccuracyStats($productId, $days = 30)
    {
        $forecasts = static::forProduct($productId)
            ->past()
            ->whereNotNull('actual_sales')
            ->where('forecast_date', '>=', now()->subDays($days))
            ->get();

        if ($forecasts->isEmpty()) {
            return null;
        }

        $accuracies = $forecasts->map->calculateAccuracy()->filter();

        return [
            'total_forecasts' => $forecasts->count(),
            'avg_accuracy' => round($accuracies->avg(), 2),
            'min_accuracy' => round($accuracies->min(), 2),
            'max_accuracy' => round($accuracies->max(), 2),
            'accurate_forecasts' => $forecasts->filter->isAccurate()->count(),
            'accuracy_rate' => round(($forecasts->filter->isAccurate()->count() / $forecasts->count()) * 100, 2),
        ];
    }

    /**
     * Calculate demand forecast using historical data
     */
    public static function calculateForecast($productId, $forecastDate, $historicalDays = 30)
    {
        $product = Product::find($productId);
        if (!$product) {
            return null;
        }

        // Get historical sales data
        $historicalSales = \DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('order_items.product_id', $productId)
            ->where('orders.created_at', '>=', now()->subDays($historicalDays))
            ->where('orders.status', 'completed')
            ->selectRaw('DATE(orders.created_at) as date, SUM(order_items.quantity) as total_sales')
            ->groupBy('date')
            ->get();

        if ($historicalSales->isEmpty()) {
            return static::createForecast($productId, $forecastDate, 0, 0.5, ['reason' => 'no_historical_data']);
        }

        // Calculate average daily sales
        $avgDailySales = $historicalSales->avg('total_sales');
        
        // Calculate trend (simple linear regression)
        $trend = static::calculateTrend($historicalSales);
        
        // Get seasonality factor (day of week)
        $dayOfWeek = $forecastDate->dayOfWeek;
        $seasonalityFactor = static::getSeasonalityFactor($productId, $dayOfWeek, $historicalDays);
        
        // Predict demand
        $predictedDemand = max(0, round($avgDailySales * (1 + $trend) * $seasonalityFactor));
        
        // Calculate confidence score (based on data consistency)
        $stdDev = static::calculateStdDev($historicalSales->pluck('total_sales'));
        $confidenceScore = min(1, max(0, 1 - ($stdDev / max($avgDailySales, 1))));
        
        // Calculate recommended price
        $currentPrice = $product->price;
        $priceElasticity = -0.5; // Assume moderate elasticity
        $demandChange = ($predictedDemand - $avgDailySales) / max($avgDailySales, 1);
        $recommendedPrice = $currentPrice * (1 - ($demandChange * $priceElasticity));
        
        $factors = [
            'avg_daily_sales' => round($avgDailySales, 2),
            'trend' => round($trend * 100, 2) . '%',
            'seasonality_factor' => round($seasonalityFactor, 2),
            'historical_days' => $historicalDays,
            'data_points' => $historicalSales->count(),
        ];

        return static::createForecast($productId, $forecastDate, $predictedDemand, $confidenceScore, $factors, $recommendedPrice);
    }

    protected static function calculateTrend($sales)
    {
        $n = $sales->count();
        if ($n < 2) return 0;

        $x = range(1, $n);
        $y = $sales->pluck('total_sales')->toArray();

        $xSum = array_sum($x);
        $ySum = array_sum($y);
        $xySum = 0;
        $xSquareSum = 0;

        for ($i = 0; $i < $n; $i++) {
            $xySum += $x[$i] * $y[$i];
            $xSquareSum += $x[$i] * $x[$i];
        }

        $slope = ($n * $xySum - $xSum * $ySum) / ($n * $xSquareSum - $xSum * $xSum);
        $avgY = $ySum / $n;

        return $avgY > 0 ? $slope / $avgY : 0;
    }

    protected static function getSeasonalityFactor($productId, $dayOfWeek, $historicalDays)
    {
        $salesByDay = \DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('order_items.product_id', $productId)
            ->where('orders.created_at', '>=', now()->subDays($historicalDays))
            ->where('orders.status', 'completed')
            ->selectRaw('DAYOFWEEK(orders.created_at) as day_of_week, AVG(order_items.quantity) as avg_sales')
            ->groupBy('day_of_week')
            ->get();

        if ($salesByDay->isEmpty()) {
            return 1.0;
        }

        $overallAvg = $salesByDay->avg('avg_sales');
        $dayAvg = $salesByDay->firstWhere('day_of_week', $dayOfWeek + 1);

        if (!$dayAvg || $overallAvg == 0) {
            return 1.0;
        }

        return $dayAvg->avg_sales / $overallAvg;
    }

    protected static function calculateStdDev($values)
    {
        $count = count($values);
        if ($count < 2) return 0;

        $mean = array_sum($values) / $count;
        $variance = array_sum(array_map(function ($x) use ($mean) {
            return pow($x - $mean, 2);
        }, $values)) / $count;

        return sqrt($variance);
    }
}
