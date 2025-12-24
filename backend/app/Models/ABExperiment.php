<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ABExperiment extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'description',
        'variants',
        'traffic_split',
        'status',
        'start_date',
        'end_date',
        'primary_metric',
        'winning_variant',
    ];

    protected $casts = [
        'variants' => 'array',
        'traffic_split' => 'array',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function results()
    {
        return $this->hasMany(ABTestResult::class, 'experiment_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where('start_date', '<=', now())
            ->where(function ($q) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', now());
            });
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }
}
