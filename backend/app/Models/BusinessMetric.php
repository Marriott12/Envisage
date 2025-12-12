<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessMetric extends Model
{
    use HasFactory;

    protected $fillable = [
        'date',
        'metric_type',
        'dimension',
        'dimension_value',
        'value',
        'count',
        'metadata',
    ];

    protected $casts = [
        'date' => 'date',
        'value' => 'decimal:2',
        'count' => 'integer',
        'metadata' => 'array',
    ];

    // Scopes
    public function scopeByMetricType($query, $metricType)
    {
        return $query->where('metric_type', $metricType);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    public function scopeByDimension($query, $dimension, $value = null)
    {
        $query->where('dimension', $dimension);

        if ($value !== null) {
            $query->where('dimension_value', $value);
        }

        return $query;
    }

    // Helper Methods
    public static function recordMetric($date, $metricType, $value, $dimension = null, $dimensionValue = null, $count = null, $metadata = null)
    {
        return self::updateOrCreate(
            [
                'date' => $date,
                'metric_type' => $metricType,
                'dimension' => $dimension,
                'dimension_value' => $dimensionValue,
            ],
            [
                'value' => $value,
                'count' => $count,
                'metadata' => $metadata,
            ]
        );
    }

    public static function getMetric($date, $metricType, $dimension = null, $dimensionValue = null)
    {
        return self::where('date', $date)
            ->where('metric_type', $metricType)
            ->where('dimension', $dimension)
            ->where('dimension_value', $dimensionValue)
            ->first();
    }

    public static function getTrend($metricType, $startDate, $endDate, $dimension = null, $dimensionValue = null)
    {
        $query = self::where('metric_type', $metricType)
            ->whereBetween('date', [$startDate, $endDate]);

        if ($dimension) {
            $query->where('dimension', $dimension);
        }

        if ($dimensionValue) {
            $query->where('dimension_value', $dimensionValue);
        }

        return $query->orderBy('date')
            ->get()
            ->map(function ($metric) {
                return [
                    'date' => $metric->date->format('Y-m-d'),
                    'value' => $metric->value,
                    'count' => $metric->count,
                ];
            });
    }
}
