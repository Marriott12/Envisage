<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AICost extends Model
{
    use HasFactory;

    protected $fillable = [
        'service',
        'date',
        'total_requests',
        'successful_requests',
        'failed_requests',
        'total_tokens',
        'total_cost_usd',
        'avg_response_time_ms',
    ];

    protected $casts = [
        'date' => 'date',
        'total_cost_usd' => 'decimal:2',
        'avg_response_time_ms' => 'decimal:2',
    ];

    public function scopeByService($query, $service)
    {
        return $query->where('service', $service);
    }

    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }
}
