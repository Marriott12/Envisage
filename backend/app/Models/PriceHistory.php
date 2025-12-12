<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PriceHistory extends Model
{
    const UPDATED_AT = null; // Only track created_at

    protected $fillable = [
        'product_id',
        'old_price',
        'new_price',
        'change_percentage',
        'change_reason',
        'rule_id',
        'user_id',
        'notes',
        'changed_at',
    ];

    protected $casts = [
        'old_price' => 'decimal:2',
        'new_price' => 'decimal:2',
        'change_percentage' => 'decimal:2',
        'changed_at' => 'datetime',
    ];

    // Change reasons
    const REASON_MANUAL = 'manual';
    const REASON_RULE_BASED = 'rule_based';
    const REASON_DEMAND = 'demand';
    const REASON_COMPETITOR = 'competitor';
    const REASON_SURGE = 'surge';

    /**
     * Relationships
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function rule()
    {
        return $this->belongsTo(PriceRule::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scopes
     */
    public function scopeForProduct($query, $productId)
    {
        return $query->where('product_id', $productId);
    }

    public function scopeByReason($query, $reason)
    {
        return $query->where('change_reason', $reason);
    }

    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('changed_at', [$startDate, $endDate]);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('changed_at', '>=', now()->subDays($days));
    }

    public function scopePriceIncreases($query)
    {
        return $query->where('change_percentage', '>', 0);
    }

    public function scopePriceDecreases($query)
    {
        return $query->where('change_percentage', '<', 0);
    }

    /**
     * Helper Methods
     */
    public static function recordChange($productId, $oldPrice, $newPrice, $reason, $ruleId = null, $userId = null, $notes = null)
    {
        $changePercentage = $oldPrice > 0 ? (($newPrice - $oldPrice) / $oldPrice) * 100 : 0;

        return static::create([
            'product_id' => $productId,
            'old_price' => $oldPrice,
            'new_price' => $newPrice,
            'change_percentage' => round($changePercentage, 2),
            'change_reason' => $reason,
            'rule_id' => $ruleId,
            'user_id' => $userId,
            'notes' => $notes,
            'changed_at' => now(),
        ]);
    }

    public function isPriceIncrease()
    {
        return $this->new_price > $this->old_price;
    }

    public function isPriceDecrease()
    {
        return $this->new_price < $this->old_price;
    }

    public function getAbsoluteChange()
    {
        return abs($this->new_price - $this->old_price);
    }

    /**
     * Get price change statistics for a product
     */
    public static function getProductStats($productId, $days = 30)
    {
        $changes = static::forProduct($productId)
            ->recent($days)
            ->get();

        if ($changes->isEmpty()) {
            return null;
        }

        return [
            'total_changes' => $changes->count(),
            'increases' => $changes->where('change_percentage', '>', 0)->count(),
            'decreases' => $changes->where('change_percentage', '<', 0)->count(),
            'avg_change_percentage' => round($changes->avg('change_percentage'), 2),
            'max_price' => $changes->max('new_price'),
            'min_price' => $changes->min('new_price'),
            'current_price' => $changes->sortByDesc('changed_at')->first()->new_price,
            'starting_price' => $changes->sortBy('changed_at')->first()->old_price,
            'by_reason' => $changes->groupBy('change_reason')->map->count(),
        ];
    }

    /**
     * Get price volatility score (0-100)
     */
    public static function getVolatilityScore($productId, $days = 30)
    {
        $changes = static::forProduct($productId)
            ->recent($days)
            ->get();

        if ($changes->count() < 2) {
            return 0;
        }

        // Calculate standard deviation of price changes
        $changePercentages = $changes->pluck('change_percentage');
        $mean = $changePercentages->avg();
        $variance = $changePercentages->map(function ($value) use ($mean) {
            return pow($value - $mean, 2);
        })->avg();

        $stdDev = sqrt($variance);

        // Normalize to 0-100 scale (assuming 10% std dev = max volatility)
        return min(100, round(($stdDev / 10) * 100, 2));
    }
}
