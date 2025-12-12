<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class StockAlert extends Model
{
    use HasFactory;

    const TYPE_LOW_STOCK = 'low_stock';
    const TYPE_OUT_OF_STOCK = 'out_of_stock';
    const TYPE_OVERSTOCK = 'overstock';
    const TYPE_EXPIRING_SOON = 'expiring_soon';
    const TYPE_SLOW_MOVING = 'slow_moving';
    const TYPE_REORDER_NEEDED = 'reorder_needed';

    const SEVERITY_LOW = 'low';
    const SEVERITY_MEDIUM = 'medium';
    const SEVERITY_HIGH = 'high';
    const SEVERITY_CRITICAL = 'critical';

    protected $fillable = [
        'product_id',
        'alert_type',
        'severity',
        'message',
        'current_stock',
        'threshold_value',
        'recommended_action',
        'is_resolved',
        'resolved_at',
        'resolved_by',
        'notes',
    ];

    protected $casts = [
        'current_stock' => 'integer',
        'threshold_value' => 'decimal:2',
        'is_resolved' => 'boolean',
        'resolved_at' => 'datetime',
    ];

    /**
     * Get the product this alert belongs to
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the user who resolved the alert
     */
    public function resolver()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    /**
     * Scope: Unresolved alerts
     */
    public function scopeUnresolved($query)
    {
        return $query->where('is_resolved', false);
    }

    /**
     * Scope: By type
     */
    public function scopeType($query, $type)
    {
        return $query->where('alert_type', $type);
    }

    /**
     * Scope: By severity
     */
    public function scopeSeverity($query, $severity)
    {
        return $query->where('severity', $severity);
    }

    /**
     * Scope: Critical alerts
     */
    public function scopeCritical($query)
    {
        return $query->where('severity', self::SEVERITY_CRITICAL);
    }

    /**
     * Scope: High priority (high + critical)
     */
    public function scopeHighPriority($query)
    {
        return $query->whereIn('severity', [self::SEVERITY_HIGH, self::SEVERITY_CRITICAL]);
    }

    /**
     * Mark as resolved
     */
    public function resolve($userId = null, $notes = null)
    {
        $this->is_resolved = true;
        $this->resolved_at = Carbon::now();
        $this->resolved_by = $userId;
        if ($notes) {
            $this->notes = $notes;
        }
        $this->save();
    }

    /**
     * Create low stock alert
     */
    public static function createLowStockAlert($productId, $currentStock, $threshold)
    {
        $percentRemaining = ($currentStock / $threshold) * 100;
        
        $severity = self::SEVERITY_LOW;
        if ($percentRemaining <= 10) {
            $severity = self::SEVERITY_CRITICAL;
        } else if ($percentRemaining <= 25) {
            $severity = self::SEVERITY_HIGH;
        } else if ($percentRemaining <= 50) {
            $severity = self::SEVERITY_MEDIUM;
        }

        return static::create([
            'product_id' => $productId,
            'alert_type' => self::TYPE_LOW_STOCK,
            'severity' => $severity,
            'message' => "Stock level is low at {$currentStock} units ({$percentRemaining}% of threshold)",
            'current_stock' => $currentStock,
            'threshold_value' => $threshold,
            'recommended_action' => 'Review reorder point and consider placing purchase order',
            'is_resolved' => false,
        ]);
    }

    /**
     * Create out of stock alert
     */
    public static function createOutOfStockAlert($productId)
    {
        return static::create([
            'product_id' => $productId,
            'alert_type' => self::TYPE_OUT_OF_STOCK,
            'severity' => self::SEVERITY_CRITICAL,
            'message' => 'Product is out of stock',
            'current_stock' => 0,
            'threshold_value' => 0,
            'recommended_action' => 'Place urgent purchase order or remove from active listings',
            'is_resolved' => false,
        ]);
    }

    /**
     * Create overstock alert
     */
    public static function createOverstockAlert($productId, $currentStock, $maxStock, $daysOfStock)
    {
        $severity = self::SEVERITY_MEDIUM;
        if ($daysOfStock > 180) {
            $severity = self::SEVERITY_HIGH;
        }

        return static::create([
            'product_id' => $productId,
            'alert_type' => self::TYPE_OVERSTOCK,
            'severity' => $severity,
            'message' => "Excess inventory detected - {$currentStock} units ({$daysOfStock} days of stock)",
            'current_stock' => $currentStock,
            'threshold_value' => $maxStock,
            'recommended_action' => 'Consider promotional pricing or reducing future orders',
            'is_resolved' => false,
        ]);
    }

    /**
     * Create reorder needed alert
     */
    public static function createReorderNeededAlert($productId, $currentStock, $reorderLevel)
    {
        return static::create([
            'product_id' => $productId,
            'alert_type' => self::TYPE_REORDER_NEEDED,
            'severity' => self::SEVERITY_HIGH,
            'message' => "Stock at {$currentStock} units has reached reorder point of {$reorderLevel}",
            'current_stock' => $currentStock,
            'threshold_value' => $reorderLevel,
            'recommended_action' => 'Place purchase order according to EOQ',
            'is_resolved' => false,
        ]);
    }

    /**
     * Create slow moving alert
     */
    public static function createSlowMovingAlert($productId, $daysSinceLastSale)
    {
        $severity = self::SEVERITY_LOW;
        if ($daysSinceLastSale > 180) {
            $severity = self::SEVERITY_HIGH;
        } else if ($daysSinceLastSale > 90) {
            $severity = self::SEVERITY_MEDIUM;
        }

        return static::create([
            'product_id' => $productId,
            'alert_type' => self::TYPE_SLOW_MOVING,
            'severity' => $severity,
            'message' => "Product has not sold in {$daysSinceLastSale} days",
            'current_stock' => 0,
            'threshold_value' => $daysSinceLastSale,
            'recommended_action' => 'Review product viability, consider clearance sale or discontinuation',
            'is_resolved' => false,
        ]);
    }

    /**
     * Check all products and generate alerts
     */
    public static function generateAllAlerts()
    {
        $alertsCreated = 0;

        // Get products with reorder points
        $reorderPoints = ReorderPoint::active()->with('product')->get();

        foreach ($reorderPoints as $rp) {
            $product = $rp->product;
            if (!$product) continue;

            $currentStock = $product->stock_quantity;

            // Check if unresolved alert already exists for this product
            $existingAlert = static::unresolved()
                ->where('product_id', $product->id)
                ->whereIn('alert_type', [self::TYPE_LOW_STOCK, self::TYPE_OUT_OF_STOCK, self::TYPE_REORDER_NEEDED])
                ->first();

            if ($existingAlert) {
                continue; // Skip if alert already exists
            }

            // Out of stock
            if ($currentStock == 0) {
                static::createOutOfStockAlert($product->id);
                $alertsCreated++;
                continue;
            }

            // Reorder needed
            if ($currentStock <= $rp->reorder_level) {
                static::createReorderNeededAlert($product->id, $currentStock, $rp->reorder_level);
                $alertsCreated++;
                continue;
            }

            // Low stock (between reorder level and reorder level * 1.5)
            if ($currentStock <= $rp->reorder_level * 1.5) {
                static::createLowStockAlert($product->id, $currentStock, $rp->reorder_level);
                $alertsCreated++;
                continue;
            }

            // Overstock (more than 180 days of stock)
            $daysOfStock = $rp->getDaysOfStockRemaining();
            if ($daysOfStock && $daysOfStock > 90) {
                static::createOverstockAlert($product->id, $currentStock, $rp->reorder_level * 2, $daysOfStock);
                $alertsCreated++;
            }
        }

        // Check for slow-moving products
        $slowMovingProducts = Product::whereDoesntHave('orderItems', function($query) {
            $query->where('created_at', '>=', Carbon::now()->subDays(60));
        })->get();

        foreach ($slowMovingProducts as $product) {
            $existingAlert = static::unresolved()
                ->where('product_id', $product->id)
                ->where('alert_type', self::TYPE_SLOW_MOVING)
                ->first();

            if (!$existingAlert) {
                $lastSale = $product->orderItems()->orderBy('created_at', 'desc')->first();
                $daysSince = $lastSale ? Carbon::parse($lastSale->created_at)->diffInDays(Carbon::now()) : 365;
                
                if ($daysSince > 60) {
                    static::createSlowMovingAlert($product->id, $daysSince);
                    $alertsCreated++;
                }
            }
        }

        return $alertsCreated;
    }

    /**
     * Get statistics
     */
    public static function getStatistics()
    {
        return [
            'total_unresolved' => static::unresolved()->count(),
            'critical_alerts' => static::unresolved()->critical()->count(),
            'high_priority_alerts' => static::unresolved()->highPriority()->count(),
            'by_type' => static::unresolved()
                ->groupBy('alert_type')
                ->selectRaw('alert_type, COUNT(*) as count')
                ->pluck('count', 'alert_type'),
            'by_severity' => static::unresolved()
                ->groupBy('severity')
                ->selectRaw('severity, COUNT(*) as count')
                ->pluck('count', 'severity'),
            'resolved_today' => static::where('resolved_at', '>=', Carbon::today())->count(),
        ];
    }

    /**
     * Auto-resolve alerts that are no longer valid
     */
    public static function autoResolveInvalidAlerts()
    {
        $resolved = 0;

        // Resolve stock alerts where stock is now adequate
        $stockAlerts = static::unresolved()
            ->whereIn('alert_type', [self::TYPE_LOW_STOCK, self::TYPE_OUT_OF_STOCK, self::TYPE_REORDER_NEEDED])
            ->with('product.reorderPoint')
            ->get();

        foreach ($stockAlerts as $alert) {
            $product = $alert->product;
            if (!$product) continue;

            $reorderPoint = $product->reorderPoint()->first();
            if (!$reorderPoint) continue;

            // If stock is now above reorder level * 1.5, resolve
            if ($product->stock_quantity > $reorderPoint->reorder_level * 1.5) {
                $alert->resolve(null, 'Auto-resolved: Stock level restored');
                $resolved++;
            }
        }

        return $resolved;
    }
}
