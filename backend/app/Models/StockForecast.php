<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class StockForecast extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'forecast_date',
        'predicted_demand',
        'confidence_level',
        'algorithm_used',
        'factors',
        'actual_demand',
        'accuracy_percentage',
    ];

    protected $casts = [
        'forecast_date' => 'date',
        'predicted_demand' => 'integer',
        'confidence_level' => 'decimal:2',
        'factors' => 'array',
        'actual_demand' => 'integer',
        'accuracy_percentage' => 'decimal:2',
    ];

    /**
     * Get the product this forecast belongs to
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Scope: Forecasts for a specific date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('forecast_date', [$startDate, $endDate]);
    }

    /**
     * Scope: High confidence forecasts
     */
    public function scopeHighConfidence($query, $threshold = 0.8)
    {
        return $query->where('confidence_level', '>=', $threshold);
    }

    /**
     * Scope: Forecasts with actual data
     */
    public function scopeWithActuals($query)
    {
        return $query->whereNotNull('actual_demand');
    }

    /**
     * Scope: Recent forecasts
     */
    public function scopeRecent($query, $days = 30)
    {
        return $query->where('forecast_date', '>=', Carbon::now()->subDays($days));
    }

    /**
     * Calculate forecast accuracy after actual demand is known
     */
    public function calculateAccuracy()
    {
        if ($this->actual_demand === null) {
            return null;
        }

        if ($this->predicted_demand == 0 && $this->actual_demand == 0) {
            $this->accuracy_percentage = 100;
        } else if ($this->predicted_demand == 0) {
            $this->accuracy_percentage = 0;
        } else {
            $error = abs($this->predicted_demand - $this->actual_demand);
            $accuracy = max(0, (1 - ($error / max($this->predicted_demand, $this->actual_demand))) * 100);
            $this->accuracy_percentage = round($accuracy, 2);
        }

        $this->save();
        return $this->accuracy_percentage;
    }

    /**
     * Generate forecast using simple moving average
     */
    public static function generateMovingAverage($productId, $forecastDate, $windowDays = 30)
    {
        $startDate = Carbon::parse($forecastDate)->subDays($windowDays);
        $endDate = Carbon::parse($forecastDate)->subDay();

        // Get historical sales
        $historicalSales = \DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('order_items.product_id', $productId)
            ->where('orders.status', 'completed')
            ->whereBetween('orders.completed_at', [$startDate, $endDate])
            ->selectRaw('DATE(orders.completed_at) as date, SUM(order_items.quantity) as daily_demand')
            ->groupBy('date')
            ->get();

        if ($historicalSales->isEmpty()) {
            return null;
        }

        $avgDemand = round($historicalSales->avg('daily_demand'));

        // Calculate confidence based on data consistency
        $stdDev = static::calculateStdDev($historicalSales->pluck('daily_demand')->toArray());
        $mean = $historicalSales->avg('daily_demand');
        $cv = $mean > 0 ? $stdDev / $mean : 1; // Coefficient of variation
        $confidence = max(0.3, min(0.95, 1 - ($cv / 2)));

        return static::create([
            'product_id' => $productId,
            'forecast_date' => $forecastDate,
            'predicted_demand' => $avgDemand,
            'confidence_level' => round($confidence, 2),
            'algorithm_used' => 'moving_average',
            'factors' => [
                'window_days' => $windowDays,
                'data_points' => $historicalSales->count(),
                'std_dev' => round($stdDev, 2),
                'coefficient_variation' => round($cv, 2),
            ],
        ]);
    }

    /**
     * Generate forecast using exponential smoothing
     */
    public static function generateExponentialSmoothing($productId, $forecastDate, $alpha = 0.3, $lookbackDays = 60)
    {
        $startDate = Carbon::parse($forecastDate)->subDays($lookbackDays);
        $endDate = Carbon::parse($forecastDate)->subDay();

        $historicalSales = \DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('order_items.product_id', $productId)
            ->where('orders.status', 'completed')
            ->whereBetween('orders.completed_at', [$startDate, $endDate])
            ->selectRaw('DATE(orders.completed_at) as date, SUM(order_items.quantity) as daily_demand')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        if ($historicalSales->isEmpty()) {
            return null;
        }

        // Apply exponential smoothing
        $forecast = $historicalSales->first()->daily_demand;
        foreach ($historicalSales as $day) {
            $forecast = $alpha * $day->daily_demand + (1 - $alpha) * $forecast;
        }

        $forecast = round($forecast);

        // Higher confidence with more data
        $confidence = min(0.95, 0.5 + ($historicalSales->count() / $lookbackDays) * 0.4);

        return static::create([
            'product_id' => $productId,
            'forecast_date' => $forecastDate,
            'predicted_demand' => $forecast,
            'confidence_level' => round($confidence, 2),
            'algorithm_used' => 'exponential_smoothing',
            'factors' => [
                'alpha' => $alpha,
                'lookback_days' => $lookbackDays,
                'data_points' => $historicalSales->count(),
            ],
        ]);
    }

    /**
     * Generate forecast considering trends and seasonality
     */
    public static function generateTrendSeasonalForecast($productId, $forecastDate, $lookbackDays = 90)
    {
        $startDate = Carbon::parse($forecastDate)->subDays($lookbackDays);
        $endDate = Carbon::parse($forecastDate)->subDay();

        $historicalSales = \DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('order_items.product_id', $productId)
            ->where('orders.status', 'completed')
            ->whereBetween('orders.completed_at', [$startDate, $endDate])
            ->selectRaw('DATE(orders.completed_at) as date, SUM(order_items.quantity) as daily_demand')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        if ($historicalSales->count() < 14) {
            return null;
        }

        // Calculate trend
        $xValues = range(1, $historicalSales->count());
        $yValues = $historicalSales->pluck('daily_demand')->toArray();
        $trend = static::calculateLinearTrend($xValues, $yValues);

        // Calculate day-of-week seasonality
        $dayOfWeek = Carbon::parse($forecastDate)->dayOfWeek;
        $seasonality = static::calculateSeasonality($historicalSales, $dayOfWeek);

        // Base forecast from recent average
        $recentAvg = collect($yValues)->slice(-14)->avg();

        // Apply trend and seasonality
        $forecast = round($recentAvg * (1 + $trend) * $seasonality);
        $forecast = max(0, $forecast);

        $confidence = min(0.9, 0.6 + ($historicalSales->count() / $lookbackDays) * 0.3);

        return static::create([
            'product_id' => $productId,
            'forecast_date' => $forecastDate,
            'predicted_demand' => $forecast,
            'confidence_level' => round($confidence, 2),
            'algorithm_used' => 'trend_seasonal',
            'factors' => [
                'lookback_days' => $lookbackDays,
                'data_points' => $historicalSales->count(),
                'trend_factor' => round($trend, 4),
                'seasonal_factor' => round($seasonality, 4),
                'day_of_week' => $dayOfWeek,
            ],
        ]);
    }

    /**
     * Calculate standard deviation
     */
    private static function calculateStdDev($values)
    {
        $count = count($values);
        if ($count < 2) return 0;

        $mean = array_sum($values) / $count;
        $variance = array_sum(array_map(function($val) use ($mean) {
            return pow($val - $mean, 2);
        }, $values)) / $count;

        return sqrt($variance);
    }

    /**
     * Calculate linear trend
     */
    private static function calculateLinearTrend($x, $y)
    {
        $n = count($x);
        $sumX = array_sum($x);
        $sumY = array_sum($y);
        $sumXY = 0;
        $sumX2 = 0;

        for ($i = 0; $i < $n; $i++) {
            $sumXY += $x[$i] * $y[$i];
            $sumX2 += $x[$i] * $x[$i];
        }

        $slope = ($n * $sumXY - $sumX * $sumY) / ($n * $sumX2 - $sumX * $sumX);
        return $slope / max(1, array_sum($y) / $n); // Normalize to percentage
    }

    /**
     * Calculate seasonality factor
     */
    private static function calculateSeasonality($historicalSales, $targetDayOfWeek)
    {
        $dayOfWeekSales = [];
        foreach ($historicalSales as $sale) {
            $dow = Carbon::parse($sale->date)->dayOfWeek;
            if (!isset($dayOfWeekSales[$dow])) {
                $dayOfWeekSales[$dow] = [];
            }
            $dayOfWeekSales[$dow][] = $sale->daily_demand;
        }

        if (!isset($dayOfWeekSales[$targetDayOfWeek])) {
            return 1.0;
        }

        $targetAvg = array_sum($dayOfWeekSales[$targetDayOfWeek]) / count($dayOfWeekSales[$targetDayOfWeek]);
        $overallAvg = $historicalSales->avg('daily_demand');

        return $overallAvg > 0 ? $targetAvg / $overallAvg : 1.0;
    }

    /**
     * Get model accuracy statistics
     */
    public static function getAccuracyStats($days = 30)
    {
        return [
            'overall_accuracy' => static::withActuals()
                ->where('created_at', '>=', Carbon::now()->subDays($days))
                ->avg('accuracy_percentage'),
            'by_algorithm' => static::withActuals()
                ->where('created_at', '>=', Carbon::now()->subDays($days))
                ->groupBy('algorithm_used')
                ->selectRaw('algorithm_used, AVG(accuracy_percentage) as avg_accuracy, COUNT(*) as count')
                ->get(),
            'high_accuracy_rate' => static::withActuals()
                ->where('created_at', '>=', Carbon::now()->subDays($days))
                ->where('accuracy_percentage', '>=', 80)
                ->count() / max(1, static::withActuals()->where('created_at', '>=', Carbon::now()->subDays($days))->count()) * 100,
        ];
    }
}
