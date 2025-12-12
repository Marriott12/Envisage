<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PriceRule extends Model
{
    protected $fillable = [
        'name',
        'product_id',
        'category_id',
        'rule_type',
        'min_price',
        'max_price',
        'target_margin',
        'conditions',
        'adjustments',
        'priority',
        'is_active',
        'starts_at',
        'ends_at',
    ];

    protected $casts = [
        'min_price' => 'decimal:2',
        'max_price' => 'decimal:2',
        'target_margin' => 'decimal:2',
        'conditions' => 'array',
        'adjustments' => 'array',
        'priority' => 'integer',
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    // Rule types
    const TYPE_DEMAND_BASED = 'demand_based';
    const TYPE_COMPETITOR_BASED = 'competitor_based';
    const TYPE_TIME_BASED = 'time_based';
    const TYPE_INVENTORY_BASED = 'inventory_based';

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

    public function priceHistory()
    {
        return $this->hasMany(PriceHistory::class, 'rule_id');
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', now());
            });
    }

    public function scopeByPriority($query)
    {
        return $query->orderBy('priority', 'desc');
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
        return $query->where('rule_type', $type);
    }

    /**
     * Helper Methods
     */
    public function isApplicable()
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->starts_at && $this->starts_at->isFuture()) {
            return false;
        }

        if ($this->ends_at && $this->ends_at->isPast()) {
            return false;
        }

        return true;
    }

    public function calculatePrice($currentPrice, $context = [])
    {
        if (!$this->isApplicable()) {
            return $currentPrice;
        }

        $adjustedPrice = $currentPrice;

        // Apply adjustments based on rule type
        switch ($this->rule_type) {
            case self::TYPE_DEMAND_BASED:
                $adjustedPrice = $this->applyDemandAdjustment($currentPrice, $context);
                break;
            case self::TYPE_COMPETITOR_BASED:
                $adjustedPrice = $this->applyCompetitorAdjustment($currentPrice, $context);
                break;
            case self::TYPE_TIME_BASED:
                $adjustedPrice = $this->applyTimeAdjustment($currentPrice, $context);
                break;
            case self::TYPE_INVENTORY_BASED:
                $adjustedPrice = $this->applyInventoryAdjustment($currentPrice, $context);
                break;
        }

        // Enforce min/max constraints
        if ($this->min_price && $adjustedPrice < $this->min_price) {
            $adjustedPrice = $this->min_price;
        }

        if ($this->max_price && $adjustedPrice > $this->max_price) {
            $adjustedPrice = $this->max_price;
        }

        return round($adjustedPrice, 2);
    }

    protected function applyDemandAdjustment($currentPrice, $context)
    {
        $demandLevel = $context['demand_level'] ?? 'normal';
        $multipliers = $this->adjustments['demand_multipliers'] ?? [
            'low' => 0.9,
            'normal' => 1.0,
            'high' => 1.1,
            'surge' => 1.2,
        ];

        return $currentPrice * ($multipliers[$demandLevel] ?? 1.0);
    }

    protected function applyCompetitorAdjustment($currentPrice, $context)
    {
        $competitorPrice = $context['competitor_price'] ?? null;
        if (!$competitorPrice) {
            return $currentPrice;
        }

        $strategy = $this->adjustments['competitor_strategy'] ?? 'match';
        $offset = $this->adjustments['price_offset'] ?? 0;

        switch ($strategy) {
            case 'undercut':
                return $competitorPrice - abs($offset);
            case 'match':
                return $competitorPrice;
            case 'premium':
                return $competitorPrice + abs($offset);
            default:
                return $currentPrice;
        }
    }

    protected function applyTimeAdjustment($currentPrice, $context)
    {
        $hour = now()->hour;
        $dayOfWeek = now()->dayOfWeek;

        $timeMultipliers = $this->adjustments['time_multipliers'] ?? [];
        
        // Check hourly multipliers
        if (isset($timeMultipliers['hours'][$hour])) {
            return $currentPrice * $timeMultipliers['hours'][$hour];
        }

        // Check day of week multipliers
        if (isset($timeMultipliers['days'][$dayOfWeek])) {
            return $currentPrice * $timeMultipliers['days'][$dayOfWeek];
        }

        return $currentPrice;
    }

    protected function applyInventoryAdjustment($currentPrice, $context)
    {
        $stockLevel = $context['stock_level'] ?? null;
        if ($stockLevel === null) {
            return $currentPrice;
        }

        $thresholds = $this->adjustments['stock_thresholds'] ?? [
            'critical' => ['max' => 5, 'multiplier' => 1.15],
            'low' => ['max' => 20, 'multiplier' => 1.1],
            'normal' => ['max' => 100, 'multiplier' => 1.0],
            'high' => ['max' => PHP_INT_MAX, 'multiplier' => 0.95],
        ];

        foreach ($thresholds as $threshold) {
            if ($stockLevel <= $threshold['max']) {
                return $currentPrice * $threshold['multiplier'];
            }
        }

        return $currentPrice;
    }

    public function checkConditions($context = [])
    {
        if (empty($this->conditions)) {
            return true;
        }

        foreach ($this->conditions as $condition) {
            $field = $condition['field'] ?? null;
            $operator = $condition['operator'] ?? '=';
            $value = $condition['value'] ?? null;

            if (!$field || !isset($context[$field])) {
                continue;
            }

            $contextValue = $context[$field];

            switch ($operator) {
                case '=':
                case '==':
                    if ($contextValue != $value) return false;
                    break;
                case '!=':
                    if ($contextValue == $value) return false;
                    break;
                case '>':
                    if ($contextValue <= $value) return false;
                    break;
                case '>=':
                    if ($contextValue < $value) return false;
                    break;
                case '<':
                    if ($contextValue >= $value) return false;
                    break;
                case '<=':
                    if ($contextValue > $value) return false;
                    break;
                case 'in':
                    if (!in_array($contextValue, (array)$value)) return false;
                    break;
                case 'not_in':
                    if (in_array($contextValue, (array)$value)) return false;
                    break;
            }
        }

        return true;
    }
}
