<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RecommendationPerformance extends Model
{
    use HasFactory;

    protected $fillable = [
        'recommendation_type',
        'algorithm',
        'date',
        'impressions',
        'clicks',
        'conversions',
        'click_through_rate',
        'conversion_rate',
        'revenue',
    ];

    protected $casts = [
        'date' => 'date',
        'impressions' => 'integer',
        'clicks' => 'integer',
        'conversions' => 'integer',
        'click_through_rate' => 'decimal:4',
        'conversion_rate' => 'decimal:4',
        'revenue' => 'decimal:2',
    ];

    // Scopes
    public function scopeByType($query, $type)
    {
        return $query->where('recommendation_type', $type);
    }

    public function scopeByAlgorithm($query, $algorithm)
    {
        return $query->where('algorithm', $algorithm);
    }

    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    // Helper methods
    public static function trackImpression($type, $algorithm)
    {
        return self::incrementMetric($type, $algorithm, 'impressions');
    }

    public static function trackClick($type, $algorithm)
    {
        self::incrementMetric($type, $algorithm, 'clicks');
        self::updateClickThroughRate($type, $algorithm);
    }

    public static function trackConversion($type, $algorithm, $revenue = 0)
    {
        self::incrementMetric($type, $algorithm, 'conversions');
        self::incrementMetric($type, $algorithm, 'revenue', $revenue);
        self::updateConversionRate($type, $algorithm);
    }

    protected static function incrementMetric($type, $algorithm, $metric, $value = 1)
    {
        $performance = self::firstOrCreate(
            [
                'recommendation_type' => $type,
                'algorithm' => $algorithm,
                'date' => today(),
            ],
            [
                'impressions' => 0,
                'clicks' => 0,
                'conversions' => 0,
                'revenue' => 0,
            ]
        );

        $performance->increment($metric, $value);
    }

    protected static function updateClickThroughRate($type, $algorithm)
    {
        $performance = self::where('recommendation_type', $type)
            ->where('algorithm', $algorithm)
            ->where('date', today())
            ->first();

        if ($performance && $performance->impressions > 0) {
            $performance->update([
                'click_through_rate' => round($performance->clicks / $performance->impressions, 4),
            ]);
        }
    }

    protected static function updateConversionRate($type, $algorithm)
    {
        $performance = self::where('recommendation_type', $type)
            ->where('algorithm', $algorithm)
            ->where('date', today())
            ->first();

        if ($performance && $performance->clicks > 0) {
            $performance->update([
                'conversion_rate' => round($performance->conversions / $performance->clicks, 4),
            ]);
        }
    }

    public static function getPerformanceReport($type = null, $days = 30)
    {
        $query = self::where('date', '>=', now()->subDays($days));

        if ($type) {
            $query->where('recommendation_type', $type);
        }

        return $query->selectRaw('
                recommendation_type,
                algorithm,
                SUM(impressions) as total_impressions,
                SUM(clicks) as total_clicks,
                SUM(conversions) as total_conversions,
                SUM(revenue) as total_revenue,
                AVG(click_through_rate) as avg_ctr,
                AVG(conversion_rate) as avg_cvr
            ')
            ->groupBy('recommendation_type', 'algorithm')
            ->orderByDesc('total_revenue')
            ->get();
    }
}
