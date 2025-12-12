<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ABTestResult extends Model
{
    use HasFactory;

    protected $table = 'ab_test_results';

    protected $fillable = [
        'test_name',
        'variant',
        'date',
        'impressions',
        'conversions',
        'conversion_rate',
        'revenue',
        'avg_order_value',
    ];

    protected $casts = [
        'date' => 'date',
        'impressions' => 'integer',
        'conversions' => 'integer',
        'conversion_rate' => 'decimal:2',
        'revenue' => 'decimal:2',
        'avg_order_value' => 'decimal:2',
    ];

    // Scopes
    public function scopeByTest($query, $testName)
    {
        return $query->where('test_name', $testName);
    }

    public function scopeByVariant($query, $variant)
    {
        return $query->where('variant', $variant);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    // Helper Methods
    public static function recordTestResult($testName, $variant, $date, $data)
    {
        $conversionRate = isset($data['impressions']) && $data['impressions'] > 0
            ? round(($data['conversions'] / $data['impressions']) * 100, 2)
            : 0;

        $avgOrderValue = isset($data['conversions']) && $data['conversions'] > 0
            ? round($data['revenue'] / $data['conversions'], 2)
            : 0;

        return self::updateOrCreate(
            [
                'test_name' => $testName,
                'variant' => $variant,
                'date' => $date,
            ],
            array_merge($data, [
                'conversion_rate' => $conversionRate,
                'avg_order_value' => $avgOrderValue,
            ])
        );
    }

    public static function getTestWinner($testName, $startDate, $endDate)
    {
        return self::where('test_name', $testName)
            ->whereBetween('date', [$startDate, $endDate])
            ->selectRaw('variant, SUM(impressions) as total_impressions, SUM(conversions) as total_conversions, SUM(revenue) as total_revenue')
            ->selectRaw('ROUND((SUM(conversions) / SUM(impressions)) * 100, 2) as overall_conversion_rate')
            ->groupBy('variant')
            ->orderByDesc('overall_conversion_rate')
            ->first();
    }
}
