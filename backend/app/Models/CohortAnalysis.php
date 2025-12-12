<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CohortAnalysis extends Model
{
    use HasFactory;

    protected $fillable = [
        'cohort_date',
        'cohort_type',
        'cohort_size',
        'period_number',
        'period_type',
        'retained_users',
        'retention_rate',
        'revenue',
        'ltv',
    ];

    protected $casts = [
        'cohort_date' => 'date',
        'cohort_size' => 'integer',
        'period_number' => 'integer',
        'retained_users' => 'integer',
        'retention_rate' => 'decimal:2',
        'revenue' => 'decimal:2',
        'ltv' => 'decimal:2',
    ];

    // Scopes
    public function scopeByCohortType($query, $cohortType)
    {
        return $query->where('cohort_type', $cohortType);
    }

    public function scopeByPeriodType($query, $periodType)
    {
        return $query->where('period_type', $periodType);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('cohort_date', [$startDate, $endDate]);
    }

    // Helper Methods
    public static function recordCohort($cohortDate, $cohortType, $cohortSize, $periodNumber, $periodType, $retainedUsers, $revenue = 0)
    {
        $retentionRate = $cohortSize > 0 ? round(($retainedUsers / $cohortSize) * 100, 2) : 0;

        return self::updateOrCreate(
            [
                'cohort_date' => $cohortDate,
                'cohort_type' => $cohortType,
                'period_number' => $periodNumber,
                'period_type' => $periodType,
            ],
            [
                'cohort_size' => $cohortSize,
                'retained_users' => $retainedUsers,
                'retention_rate' => $retentionRate,
                'revenue' => $revenue,
                'ltv' => $cohortSize > 0 ? round($revenue / $cohortSize, 2) : 0,
            ]
        );
    }
}
