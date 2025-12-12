<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SurgePricingEvent extends Model
{
    protected $fillable = [
        'product_id',
        'category_id',
        'event_type',
        'surge_multiplier',
        'demand_spike',
        'stock_level',
        'surge_started_at',
        'surge_ended_at',
        'is_active',
    ];

    protected $casts = [
        'surge_multiplier' => 'decimal:2',
        'demand_spike' => 'decimal:2',
        'stock_level' => 'integer',
        'surge_started_at' => 'datetime',
        'surge_ended_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    // Event types
    const TYPE_FLASH_SALE = 'flash_sale';
    const TYPE_HOLIDAY = 'holiday';
    const TYPE_STOCK_LOW = 'stock_low';
    const TYPE_HIGH_TRAFFIC = 'high_traffic';

    /**
     * Relationships
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForProduct($query, $productId)
    {
        return $query->where('product_id', $productId);
    }

    public function scopeForCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('event_type', $type);
    }

    public function scopeCurrent($query)
    {
        return $query->where('is_active', true)
            ->where('surge_started_at', '<=', now())
            ->where(function ($q) {
                $q->whereNull('surge_ended_at')
                    ->orWhere('surge_ended_at', '>=', now());
            });
    }

    /**
     * Helper Methods
     */
    public static function activateSurge($productId, $categoryId, $eventType, $surgeMultiplier, $demandSpike = null, $stockLevel = null, $duration = null)
    {
        $surgeEndedAt = $duration ? now()->addMinutes($duration) : null;

        return static::create([
            'product_id' => $productId,
            'category_id' => $categoryId,
            'event_type' => $eventType,
            'surge_multiplier' => $surgeMultiplier,
            'demand_spike' => $demandSpike,
            'stock_level' => $stockLevel,
            'surge_started_at' => now(),
            'surge_ended_at' => $surgeEndedAt,
            'is_active' => true,
        ]);
    }

    public function deactivate()
    {
        $this->update([
            'is_active' => false,
            'surge_ended_at' => now(),
        ]);
    }

    public function isCurrentlyActive()
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->surge_started_at->isFuture()) {
            return false;
        }

        if ($this->surge_ended_at && $this->surge_ended_at->isPast()) {
            return false;
        }

        return true;
    }

    public function calculateSurgePrice($basePrice)
    {
        if (!$this->isCurrentlyActive()) {
            return $basePrice;
        }

        return round($basePrice * $this->surge_multiplier, 2);
    }

    public function getDuration()
    {
        if (!$this->surge_ended_at) {
            return null;
        }

        return $this->surge_started_at->diffInMinutes($this->surge_ended_at);
    }

    public function getTimeRemaining()
    {
        if (!$this->surge_ended_at || !$this->isCurrentlyActive()) {
            return null;
        }

        return now()->diffInMinutes($this->surge_ended_at, false);
    }

    /**
     * Check if surge pricing should be activated based on conditions
     */
    public static function checkSurgeConditions($productId)
    {
        $product = Product::find($productId);
        if (!$product) {
            return null;
        }

        $conditions = [
            'low_stock' => static::checkLowStock($product),
            'high_demand' => static::checkHighDemand($product),
            'high_traffic' => static::checkHighTraffic($product),
        ];

        foreach ($conditions as $type => $shouldActivate) {
            if ($shouldActivate) {
                return static::autoActivateSurge($product, $type);
            }
        }

        return null;
    }

    protected static function checkLowStock($product)
    {
        $stockThreshold = 10; // Consider low when < 10 units
        return $product->stock < $stockThreshold;
    }

    protected static function checkHighDemand($product)
    {
        // Check if sales in last hour exceed average hourly sales
        $recentSales = \DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('order_items.product_id', $product->id)
            ->where('orders.created_at', '>=', now()->subHour())
            ->where('orders.status', 'completed')
            ->sum('order_items.quantity');

        $avgHourlySales = \DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('order_items.product_id', $product->id)
            ->where('orders.created_at', '>=', now()->subDays(7))
            ->where('orders.status', 'completed')
            ->sum('order_items.quantity') / (7 * 24); // Average per hour over 7 days

        return $recentSales > ($avgHourlySales * 3); // 3x spike
    }

    protected static function checkHighTraffic($product)
    {
        // Check product views in last hour
        $recentViews = \DB::table('analytic_events')
            ->where('event_type', 'product_view')
            ->where('properties->product_id', $product->id)
            ->where('created_at', '>=', now()->subHour())
            ->count();

        $avgHourlyViews = \DB::table('analytic_events')
            ->where('event_type', 'product_view')
            ->where('properties->product_id', $product->id)
            ->where('created_at', '>=', now()->subDays(7))
            ->count() / (7 * 24);

        return $recentViews > ($avgHourlyViews * 5); // 5x spike
    }

    protected static function autoActivateSurge($product, $type)
    {
        // Check if surge already active
        $existingSurge = static::forProduct($product->id)
            ->current()
            ->first();

        if ($existingSurge) {
            return $existingSurge;
        }

        // Determine surge multiplier based on type
        $multipliers = [
            'low_stock' => 1.15, // 15% increase
            'high_demand' => 1.20, // 20% increase
            'high_traffic' => 1.10, // 10% increase
        ];

        $surgeMultiplier = $multipliers[$type] ?? 1.1;

        // Calculate demand spike
        $recentSales = \DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('order_items.product_id', $product->id)
            ->where('orders.created_at', '>=', now()->subHour())
            ->where('orders.status', 'completed')
            ->sum('order_items.quantity');

        $eventType = $type === 'low_stock' ? self::TYPE_STOCK_LOW : self::TYPE_HIGH_TRAFFIC;

        return static::activateSurge(
            $product->id,
            $product->category_id,
            $eventType,
            $surgeMultiplier,
            $recentSales,
            $product->stock,
            60 // 1 hour duration
        );
    }

    /**
     * Get active surge pricing for product
     */
    public static function getActiveSurge($productId)
    {
        return static::forProduct($productId)
            ->current()
            ->orderBy('surge_multiplier', 'desc')
            ->first();
    }

    /**
     * Get surge pricing summary
     */
    public static function getSurgeSummary($productId)
    {
        $activeSurge = static::getActiveSurge($productId);
        
        if (!$activeSurge) {
            return [
                'has_surge' => false,
                'message' => 'No active surge pricing',
            ];
        }

        $product = Product::find($productId);
        $basePrice = $product->price;
        $surgePrice = $activeSurge->calculateSurgePrice($basePrice);

        return [
            'has_surge' => true,
            'event_type' => $activeSurge->event_type,
            'base_price' => $basePrice,
            'surge_price' => $surgePrice,
            'surge_multiplier' => $activeSurge->surge_multiplier,
            'increase_percentage' => round(($activeSurge->surge_multiplier - 1) * 100, 2),
            'time_remaining' => $activeSurge->getTimeRemaining(),
            'demand_spike' => $activeSurge->demand_spike,
            'stock_level' => $activeSurge->stock_level,
            'message' => static::getSurgeMessage($activeSurge),
        ];
    }

    protected static function getSurgeMessage($surge)
    {
        $messages = [
            self::TYPE_FLASH_SALE => 'Flash sale pricing in effect',
            self::TYPE_HOLIDAY => 'Holiday surge pricing active',
            self::TYPE_STOCK_LOW => 'Limited stock - surge pricing active',
            self::TYPE_HIGH_TRAFFIC => 'High demand - surge pricing active',
        ];

        return $messages[$surge->event_type] ?? 'Surge pricing active';
    }
}
