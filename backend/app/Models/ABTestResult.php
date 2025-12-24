<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ABTestResult extends Model
{
    use HasFactory;

    protected $table = 'ab_test_results';

    protected $fillable = [
        'experiment_id',
        'user_id',
        'variant',
        'metric_name',
        'metric_value',
        'metadata',
    ];

    protected $casts = [
        'metric_value' => 'decimal:4',
        'metadata' => 'array',
    ];

    public function experiment()
    {
        return $this->belongsTo(ABExperiment::class, 'experiment_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeByVariant($query, $variant)
    {
        return $query->where('variant', $variant);
    }

    public function scopeByMetric($query, $metricName)
    {
        return $query->where('metric_name', $metricName);
    }

    public function scopeByExperiment($query, $experimentId)
    {
        return $query->where('experiment_id', $experimentId);
    }
}
