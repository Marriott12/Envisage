<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'country',
        'lead_time_days',
        'minimum_order_quantity',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'lead_time_days' => 'integer',
        'minimum_order_quantity' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Get purchase orders for this supplier
     */
    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    /**
     * Get performance metrics for this supplier
     */
    public function performanceMetrics()
    {
        return $this->hasMany(SupplierPerformance::class);
    }

    /**
     * Get reorder points that use this supplier
     */
    public function reorderPoints()
    {
        return $this->hasMany(ReorderPoint::class);
    }

    /**
     * Get the latest performance metric
     */
    public function latestPerformance()
    {
        return $this->hasOne(SupplierPerformance::class)->latestOfMany();
    }

    /**
     * Scope: Only active suppliers
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Suppliers with low lead time
     */
    public function scopeFastDelivery($query, $maxDays = 7)
    {
        return $query->where('lead_time_days', '<=', $maxDays);
    }

    /**
     * Get average performance rating
     */
    public function getAverageRatingAttribute()
    {
        return $this->performanceMetrics()
            ->whereNotNull('performance_rating')
            ->avg('performance_rating');
    }

    /**
     * Get on-time delivery percentage
     */
    public function getOnTimeDeliveryRateAttribute()
    {
        $total = $this->purchaseOrders()
            ->whereNotNull('received_date')
            ->count();

        if ($total === 0) {
            return null;
        }

        $onTime = $this->purchaseOrders()
            ->whereNotNull('received_date')
            ->whereRaw('received_date <= expected_delivery_date')
            ->count();

        return round(($onTime / $total) * 100, 2);
    }

    /**
     * Get average lead time from actual orders
     */
    public function getActualLeadTimeDaysAttribute()
    {
        return $this->purchaseOrders()
            ->whereNotNull('received_date')
            ->selectRaw('AVG(DATEDIFF(received_date, order_date)) as avg_days')
            ->value('avg_days');
    }

    /**
     * Check if supplier meets performance standards
     */
    public function meetsPerformanceStandards()
    {
        $latestPerf = $this->latestPerformance;

        if (!$latestPerf) {
            return true; // New suppliers get benefit of doubt
        }

        return $latestPerf->performance_rating >= 3.5 &&
               $latestPerf->on_time_delivery_rate >= 85;
    }

    /**
     * Get supplier reliability score (0-100)
     */
    public function getReliabilityScore()
    {
        $latestPerf = $this->latestPerformance;

        if (!$latestPerf) {
            return 50; // Neutral score for new suppliers
        }

        $score = 0;

        // Performance rating (40 points)
        $score += ($latestPerf->performance_rating / 5) * 40;

        // On-time delivery (30 points)
        $score += ($latestPerf->on_time_delivery_rate / 100) * 30;

        // Quality rate (20 points)
        $score += ($latestPerf->quality_rate / 100) * 20;

        // Response time (10 points)
        $maxResponseHours = 48;
        $responseScore = max(0, ($maxResponseHours - $latestPerf->avg_response_time_hours) / $maxResponseHours);
        $score += $responseScore * 10;

        return round($score, 2);
    }

    /**
     * Get statistics for all suppliers
     */
    public static function getStatistics()
    {
        return [
            'total_suppliers' => static::count(),
            'active_suppliers' => static::active()->count(),
            'avg_lead_time' => static::active()->avg('lead_time_days'),
            'fast_delivery_suppliers' => static::active()->fastDelivery()->count(),
            'top_rated' => static::active()
                ->join('supplier_performance', function($join) {
                    $join->on('suppliers.id', '=', 'supplier_performance.supplier_id')
                        ->whereRaw('supplier_performance.id IN (SELECT MAX(id) FROM supplier_performance GROUP BY supplier_id)');
                })
                ->where('supplier_performance.performance_rating', '>=', 4.5)
                ->count(),
        ];
    }

    /**
     * Recommend best supplier for a product
     */
    public static function recommendForProduct($productId, $quantity)
    {
        return static::active()
            ->join('reorder_points', 'suppliers.id', '=', 'reorder_points.supplier_id')
            ->where('reorder_points.product_id', $productId)
            ->where('suppliers.minimum_order_quantity', '<=', $quantity)
            ->leftJoin('supplier_performance', function($join) {
                $join->on('suppliers.id', '=', 'supplier_performance.supplier_id')
                    ->whereRaw('supplier_performance.id IN (SELECT MAX(id) FROM supplier_performance GROUP BY supplier_id)');
            })
            ->select('suppliers.*')
            ->selectRaw('COALESCE(supplier_performance.performance_rating, 3) as rating')
            ->selectRaw('suppliers.lead_time_days as lead_time')
            ->orderByRaw('rating DESC, lead_time ASC')
            ->first();
    }
}
