<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class ReorderPoint extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'supplier_id',
        'reorder_level',
        'safety_stock',
        'economic_order_quantity',
        'lead_time_days',
        'avg_daily_demand',
        'demand_variability',
        'service_level',
        'is_active',
        'last_triggered_at',
    ];

    protected $casts = [
        'reorder_level' => 'integer',
        'safety_stock' => 'integer',
        'economic_order_quantity' => 'integer',
        'lead_time_days' => 'integer',
        'avg_daily_demand' => 'decimal:2',
        'demand_variability' => 'decimal:4',
        'service_level' => 'decimal:2',
        'is_active' => 'boolean',
        'last_triggered_at' => 'datetime',
    ];

    /**
     * Get the product this reorder point belongs to
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the supplier for this reorder point
     */
    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Get purchase orders triggered by this reorder point
     */
    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    /**
     * Scope: Active reorder points
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Reorder points that need triggering
     */
    public function scopeNeedsTrigger($query)
    {
        return $query->active()
            ->whereRaw('(SELECT stock_quantity FROM products WHERE products.id = reorder_points.product_id) <= reorder_points.reorder_level')
            ->where(function($q) {
                $q->whereNull('last_triggered_at')
                  ->orWhere('last_triggered_at', '<=', Carbon::now()->subDays(7));
            });
    }

    /**
     * Calculate optimal reorder level
     * Formula: Reorder Level = (Average Daily Demand × Lead Time) + Safety Stock
     */
    public function calculateReorderLevel()
    {
        $this->reorder_level = ceil(
            ($this->avg_daily_demand * $this->lead_time_days) + $this->safety_stock
        );
        $this->save();
        return $this->reorder_level;
    }

    /**
     * Calculate safety stock
     * Formula: Safety Stock = Z-score × Standard Deviation × √Lead Time
     * Z-score for 95% service level ≈ 1.65
     * Z-score for 99% service level ≈ 2.33
     */
    public function calculateSafetyStock()
    {
        // Map service level to Z-score
        $zScores = [
            90 => 1.28,
            95 => 1.65,
            97 => 1.88,
            99 => 2.33,
            99.9 => 3.09,
        ];

        $zScore = $zScores[95]; // Default to 95%
        foreach ($zScores as $level => $z) {
            if ($this->service_level >= $level) {
                $zScore = $z;
            }
        }

        $stdDev = $this->avg_daily_demand * $this->demand_variability;
        $this->safety_stock = ceil($zScore * $stdDev * sqrt($this->lead_time_days));
        $this->save();
        return $this->safety_stock;
    }

    /**
     * Calculate Economic Order Quantity (EOQ)
     * Formula: EOQ = √((2 × Annual Demand × Order Cost) / Holding Cost per Unit)
     * Simplified: Using typical values - Order cost: $50, Holding cost: 20% of unit price
     */
    public function calculateEOQ()
    {
        $annualDemand = $this->avg_daily_demand * 365;
        $orderCost = 50; // Typical ordering cost
        $unitPrice = $this->product->price ?? 10;
        $holdingCostRate = 0.2; // 20% of unit price per year
        $holdingCost = $unitPrice * $holdingCostRate;

        if ($holdingCost <= 0) {
            $this->economic_order_quantity = ceil($annualDemand / 12); // Monthly supply
        } else {
            $eoq = sqrt((2 * $annualDemand * $orderCost) / $holdingCost);
            $this->economic_order_quantity = ceil($eoq);
        }

        $this->save();
        return $this->economic_order_quantity;
    }

    /**
     * Update from historical sales data
     */
    public function updateFromHistory($days = 60)
    {
        $sales = \DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('order_items.product_id', $this->product_id)
            ->where('orders.status', 'completed')
            ->where('orders.completed_at', '>=', Carbon::now()->subDays($days))
            ->selectRaw('DATE(orders.completed_at) as date, SUM(order_items.quantity) as daily_quantity')
            ->groupBy('date')
            ->get();

        if ($sales->isEmpty()) {
            return false;
        }

        // Calculate average daily demand
        $this->avg_daily_demand = $sales->avg('daily_quantity');

        // Calculate demand variability (coefficient of variation)
        $quantities = $sales->pluck('daily_quantity')->toArray();
        $mean = array_sum($quantities) / count($quantities);
        $variance = array_sum(array_map(function($q) use ($mean) {
            return pow($q - $mean, 2);
        }, $quantities)) / count($quantities);
        $stdDev = sqrt($variance);
        $this->demand_variability = $mean > 0 ? $stdDev / $mean : 0;

        // Use supplier's lead time or default
        if ($this->supplier) {
            $this->lead_time_days = $this->supplier->lead_time_days;
        }

        // Set default service level if not set
        if (!$this->service_level) {
            $this->service_level = 95;
        }

        $this->save();

        // Recalculate everything
        $this->calculateSafetyStock();
        $this->calculateReorderLevel();
        $this->calculateEOQ();

        return true;
    }

    /**
     * Check if reorder is needed
     */
    public function needsReorder()
    {
        $currentStock = $this->product->stock_quantity ?? 0;
        return $currentStock <= $this->reorder_level;
    }

    /**
     * Get recommended order quantity
     */
    public function getRecommendedOrderQuantity()
    {
        $currentStock = $this->product->stock_quantity ?? 0;
        $deficit = max(0, $this->reorder_level - $currentStock);
        
        // Order at least the EOQ or enough to reach reorder level
        return max($this->economic_order_quantity, $deficit + $this->safety_stock);
    }

    /**
     * Calculate days of stock remaining
     */
    public function getDaysOfStockRemaining()
    {
        if ($this->avg_daily_demand <= 0) {
            return null;
        }

        $currentStock = $this->product->stock_quantity ?? 0;
        return round($currentStock / $this->avg_daily_demand, 1);
    }

    /**
     * Calculate stockout risk percentage
     */
    public function getStockoutRisk()
    {
        $currentStock = $this->product->stock_quantity ?? 0;
        $daysRemaining = $this->getDaysOfStockRemaining();

        if ($daysRemaining === null) {
            return 0;
        }

        // High risk if less than lead time
        if ($daysRemaining <= $this->lead_time_days) {
            return 90;
        }

        // Medium risk if less than lead time + safety buffer
        if ($daysRemaining <= $this->lead_time_days * 1.5) {
            return 50;
        }

        // Low risk if above reorder level
        if ($currentStock > $this->reorder_level) {
            return 10;
        }

        return 30;
    }

    /**
     * Mark as triggered
     */
    public function markTriggered()
    {
        $this->last_triggered_at = Carbon::now();
        $this->save();
    }

    /**
     * Get statistics for all reorder points
     */
    public static function getStatistics()
    {
        return [
            'total_products' => static::active()->count(),
            'needs_reorder' => static::needsTrigger()->count(),
            'avg_safety_stock_days' => \DB::table('reorder_points')
                ->where('is_active', true)
                ->whereRaw('avg_daily_demand > 0')
                ->selectRaw('AVG(safety_stock / avg_daily_demand) as avg_days')
                ->value('avg_days'),
            'high_stockout_risk' => static::active()
                ->whereRaw('(SELECT stock_quantity FROM products WHERE products.id = reorder_points.product_id) / avg_daily_demand <= lead_time_days')
                ->count(),
            'avg_eoq' => static::active()->avg('economic_order_quantity'),
        ];
    }

    /**
     * Initialize from product and supplier
     */
    public static function createFromProduct($productId, $supplierId, $serviceLevelPercent = 95)
    {
        $reorderPoint = static::create([
            'product_id' => $productId,
            'supplier_id' => $supplierId,
            'service_level' => $serviceLevelPercent,
            'is_active' => true,
        ]);

        $reorderPoint->updateFromHistory();

        return $reorderPoint;
    }
}
