<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class SupplierPerformance extends Model
{
    use HasFactory;

    protected $table = 'supplier_performance';

    protected $fillable = [
        'supplier_id',
        'evaluation_period_start',
        'evaluation_period_end',
        'total_orders',
        'on_time_deliveries',
        'on_time_delivery_rate',
        'avg_lead_time_days',
        'quality_rate',
        'defect_rate',
        'avg_response_time_hours',
        'performance_rating',
        'notes',
    ];

    protected $casts = [
        'evaluation_period_start' => 'date',
        'evaluation_period_end' => 'date',
        'total_orders' => 'integer',
        'on_time_deliveries' => 'integer',
        'on_time_delivery_rate' => 'decimal:2',
        'avg_lead_time_days' => 'decimal:2',
        'quality_rate' => 'decimal:2',
        'defect_rate' => 'decimal:2',
        'avg_response_time_hours' => 'decimal:2',
        'performance_rating' => 'decimal:2',
    ];

    /**
     * Get the supplier this performance record belongs to
     */
    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Scope: Recent evaluations
     */
    public function scopeRecent($query, $months = 6)
    {
        return $query->where('evaluation_period_end', '>=', Carbon::now()->subMonths($months));
    }

    /**
     * Scope: High performers
     */
    public function scopeHighPerformers($query, $minRating = 4.0)
    {
        return $query->where('performance_rating', '>=', $minRating);
    }

    /**
     * Scope: Poor performers
     */
    public function scopePoorPerformers($query, $maxRating = 3.0)
    {
        return $query->where('performance_rating', '<=', $maxRating);
    }

    /**
     * Calculate and store performance metrics for a supplier
     */
    public static function evaluateSupplier($supplierId, $startDate, $endDate)
    {
        $supplier = Supplier::find($supplierId);
        if (!$supplier) {
            return null;
        }

        // Get all completed POs in period
        $orders = PurchaseOrder::where('supplier_id', $supplierId)
            ->where('status', PurchaseOrder::STATUS_RECEIVED)
            ->whereBetween('order_date', [$startDate, $endDate])
            ->get();

        if ($orders->isEmpty()) {
            return null;
        }

        // Calculate metrics
        $totalOrders = $orders->count();
        $onTimeDeliveries = $orders->filter(function($po) {
            return $po->wasOnTime();
        })->count();
        $onTimeRate = ($onTimeDeliveries / $totalOrders) * 100;

        $avgLeadTime = $orders->avg(function($po) {
            return $po->getActualLeadTimeDays();
        });

        // Quality and defect rate would need product returns/complaints data
        // For now, use placeholder values
        $qualityRate = 95; // Default high quality
        $defectRate = 5;   // Default low defects

        // Response time would need support ticket data
        $avgResponseTime = 24; // Default 24 hours

        // Calculate overall performance rating (1-5 scale)
        $rating = 0;
        
        // On-time delivery (40% weight)
        $rating += ($onTimeRate / 100) * 2;
        
        // Lead time (30% weight) - Compare to promised lead time
        $leadTimeScore = max(0, 1 - (abs($avgLeadTime - $supplier->lead_time_days) / $supplier->lead_time_days));
        $rating += $leadTimeScore * 1.5;
        
        // Quality (20% weight)
        $rating += ($qualityRate / 100) * 1;
        
        // Response time (10% weight) - Good if under 24 hours
        $responseScore = max(0, 1 - ($avgResponseTime / 48));
        $rating += $responseScore * 0.5;

        return static::create([
            'supplier_id' => $supplierId,
            'evaluation_period_start' => $startDate,
            'evaluation_period_end' => $endDate,
            'total_orders' => $totalOrders,
            'on_time_deliveries' => $onTimeDeliveries,
            'on_time_delivery_rate' => round($onTimeRate, 2),
            'avg_lead_time_days' => round($avgLeadTime, 2),
            'quality_rate' => $qualityRate,
            'defect_rate' => $defectRate,
            'avg_response_time_hours' => $avgResponseTime,
            'performance_rating' => round($rating, 2),
        ]);
    }

    /**
     * Get performance trend (improving, stable, declining)
     */
    public function getTrend()
    {
        $previousEval = static::where('supplier_id', $this->supplier_id)
            ->where('evaluation_period_end', '<', $this->evaluation_period_start)
            ->orderBy('evaluation_period_end', 'desc')
            ->first();

        if (!$previousEval) {
            return 'new';
        }

        $ratingDiff = $this->performance_rating - $previousEval->performance_rating;

        if ($ratingDiff > 0.3) return 'improving';
        if ($ratingDiff < -0.3) return 'declining';
        return 'stable';
    }

    /**
     * Get performance grade (A, B, C, D, F)
     */
    public function getGrade()
    {
        if ($this->performance_rating >= 4.5) return 'A';
        if ($this->performance_rating >= 3.5) return 'B';
        if ($this->performance_rating >= 2.5) return 'C';
        if ($this->performance_rating >= 1.5) return 'D';
        return 'F';
    }

    /**
     * Get recommendations based on performance
     */
    public function getRecommendations()
    {
        $recommendations = [];

        if ($this->on_time_delivery_rate < 85) {
            $recommendations[] = 'Improve on-time delivery rate - currently at ' . $this->on_time_delivery_rate . '%';
        }

        if ($this->avg_lead_time_days > $this->supplier->lead_time_days * 1.2) {
            $recommendations[] = 'Reduce lead time - averaging ' . $this->avg_lead_time_days . ' days vs promised ' . $this->supplier->lead_time_days;
        }

        if ($this->quality_rate < 90) {
            $recommendations[] = 'Address quality issues - quality rate at ' . $this->quality_rate . '%';
        }

        if ($this->avg_response_time_hours > 48) {
            $recommendations[] = 'Improve communication - response time averaging ' . $this->avg_response_time_hours . ' hours';
        }

        if ($this->performance_rating < 2.5) {
            $recommendations[] = 'Consider finding alternative supplier - overall rating is ' . $this->performance_rating . '/5';
        }

        if (empty($recommendations)) {
            $recommendations[] = 'Excellent performance - continue partnership';
        }

        return $recommendations;
    }

    /**
     * Evaluate all suppliers for a period
     */
    public static function evaluateAllSuppliers($startDate = null, $endDate = null)
    {
        if (!$startDate) {
            $startDate = Carbon::now()->subMonth()->startOfMonth();
        }
        if (!$endDate) {
            $endDate = Carbon::now()->subMonth()->endOfMonth();
        }

        $suppliers = Supplier::active()->get();
        $evaluated = 0;

        foreach ($suppliers as $supplier) {
            $result = static::evaluateSupplier($supplier->id, $startDate, $endDate);
            if ($result) {
                $evaluated++;
            }
        }

        return $evaluated;
    }

    /**
     * Get statistics
     */
    public static function getStatistics($months = 6)
    {
        $startDate = Carbon::now()->subMonths($months);

        return [
            'avg_rating' => static::recent($months)->avg('performance_rating'),
            'avg_on_time_rate' => static::recent($months)->avg('on_time_delivery_rate'),
            'avg_lead_time' => static::recent($months)->avg('avg_lead_time_days'),
            'high_performers' => static::recent($months)->highPerformers()->count(),
            'poor_performers' => static::recent($months)->poorPerformers()->count(),
            'top_suppliers' => static::recent($months)
                ->with('supplier')
                ->orderBy('performance_rating', 'desc')
                ->limit(5)
                ->get(),
        ];
    }
}
