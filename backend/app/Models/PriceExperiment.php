<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PriceExperiment extends Model
{
    protected $fillable = [
        'name',
        'product_id',
        'control_price',
        'variant_price',
        'status',
        'control_impressions',
        'control_sales',
        'control_revenue',
        'control_conversion_rate',
        'variant_impressions',
        'variant_sales',
        'variant_revenue',
        'variant_conversion_rate',
        'winner',
        'confidence_level',
        'started_at',
        'ended_at',
    ];

    protected $casts = [
        'control_price' => 'decimal:2',
        'variant_price' => 'decimal:2',
        'control_impressions' => 'integer',
        'control_sales' => 'integer',
        'control_revenue' => 'decimal:2',
        'control_conversion_rate' => 'decimal:4',
        'variant_impressions' => 'integer',
        'variant_sales' => 'integer',
        'variant_revenue' => 'decimal:2',
        'variant_conversion_rate' => 'decimal:4',
        'confidence_level' => 'decimal:4',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    // Status types
    const STATUS_ACTIVE = 'active';
    const STATUS_PAUSED = 'paused';
    const STATUS_COMPLETED = 'completed';

    // Winner types
    const WINNER_CONTROL = 'control';
    const WINNER_VARIANT = 'variant';
    const WINNER_NONE = 'none';

    /**
     * Relationships
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeForProduct($query, $productId)
    {
        return $query->where('product_id', $productId);
    }

    /**
     * Helper Methods
     */
    public static function startExperiment($productId, $name, $controlPrice, $variantPrice)
    {
        return static::create([
            'name' => $name,
            'product_id' => $productId,
            'control_price' => $controlPrice,
            'variant_price' => $variantPrice,
            'status' => self::STATUS_ACTIVE,
            'control_impressions' => 0,
            'control_sales' => 0,
            'control_revenue' => 0,
            'control_conversion_rate' => 0,
            'variant_impressions' => 0,
            'variant_sales' => 0,
            'variant_revenue' => 0,
            'variant_conversion_rate' => 0,
            'started_at' => now(),
        ]);
    }

    public function recordImpression($isVariant = false)
    {
        if ($isVariant) {
            $this->increment('variant_impressions');
        } else {
            $this->increment('control_impressions');
        }
        
        $this->updateConversionRates();
    }

    public function recordSale($isVariant = false, $amount = null)
    {
        if ($isVariant) {
            $this->increment('variant_sales');
            if ($amount) {
                $this->increment('variant_revenue', $amount);
            }
        } else {
            $this->increment('control_sales');
            if ($amount) {
                $this->increment('control_revenue', $amount);
            }
        }
        
        $this->updateConversionRates();
    }

    protected function updateConversionRates()
    {
        $controlRate = $this->control_impressions > 0 
            ? $this->control_sales / $this->control_impressions 
            : 0;
            
        $variantRate = $this->variant_impressions > 0 
            ? $this->variant_sales / $this->variant_impressions 
            : 0;

        $this->update([
            'control_conversion_rate' => round($controlRate, 4),
            'variant_conversion_rate' => round($variantRate, 4),
        ]);
    }

    public function calculateStatisticalSignificance()
    {
        $n1 = $this->control_impressions;
        $n2 = $this->variant_impressions;
        $p1 = $this->control_conversion_rate;
        $p2 = $this->variant_conversion_rate;

        if ($n1 < 30 || $n2 < 30) {
            return 0; // Not enough data
        }

        // Calculate pooled probability
        $p = (($p1 * $n1) + ($p2 * $n2)) / ($n1 + $n2);
        
        // Calculate standard error
        $se = sqrt($p * (1 - $p) * ((1 / $n1) + (1 / $n2)));
        
        if ($se == 0) {
            return 0;
        }

        // Calculate z-score
        $z = abs($p2 - $p1) / $se;
        
        // Convert z-score to confidence level (simplified)
        // z = 1.96 corresponds to 95% confidence
        $confidence = min(0.9999, max(0, 1 - (2 * (1 - $this->normalCDF($z)))));
        
        $this->update(['confidence_level' => round($confidence, 4)]);
        
        return $confidence;
    }

    protected function normalCDF($z)
    {
        // Approximation of cumulative distribution function
        return 0.5 * (1 + erf($z / sqrt(2)));
    }

    public function determineWinner($minConfidence = 0.95, $minImpressions = 100)
    {
        if ($this->control_impressions < $minImpressions || $this->variant_impressions < $minImpressions) {
            return null; // Not enough data
        }

        $confidence = $this->calculateStatisticalSignificance();

        if ($confidence < $minConfidence) {
            return self::WINNER_NONE; // No statistical significance
        }

        // Compare by revenue per impression (value metric)
        $controlValue = $this->control_impressions > 0 
            ? $this->control_revenue / $this->control_impressions 
            : 0;
            
        $variantValue = $this->variant_impressions > 0 
            ? $this->variant_revenue / $this->variant_impressions 
            : 0;

        $winner = $variantValue > $controlValue ? self::WINNER_VARIANT : self::WINNER_CONTROL;
        
        $this->update(['winner' => $winner]);
        
        return $winner;
    }

    public function completeExperiment()
    {
        $this->determineWinner();
        
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'ended_at' => now(),
        ]);

        return $this->getResults();
    }

    public function pauseExperiment()
    {
        $this->update(['status' => self::STATUS_PAUSED]);
    }

    public function resumeExperiment()
    {
        $this->update(['status' => self::STATUS_ACTIVE]);
    }

    public function getResults()
    {
        $this->calculateStatisticalSignificance();
        $this->determineWinner();

        $controlRPV = $this->control_impressions > 0 
            ? $this->control_revenue / $this->control_impressions 
            : 0;
            
        $variantRPV = $this->variant_impressions > 0 
            ? $this->variant_revenue / $this->variant_impressions 
            : 0;

        $improvement = $controlRPV > 0 
            ? (($variantRPV - $controlRPV) / $controlRPV) * 100 
            : 0;

        return [
            'experiment_name' => $this->name,
            'product_id' => $this->product_id,
            'status' => $this->status,
            'duration_days' => $this->started_at->diffInDays($this->ended_at ?? now()),
            'control' => [
                'price' => $this->control_price,
                'impressions' => $this->control_impressions,
                'sales' => $this->control_sales,
                'revenue' => $this->control_revenue,
                'conversion_rate' => round($this->control_conversion_rate * 100, 2) . '%',
                'revenue_per_visitor' => round($controlRPV, 2),
            ],
            'variant' => [
                'price' => $this->variant_price,
                'impressions' => $this->variant_impressions,
                'sales' => $this->variant_sales,
                'revenue' => $this->variant_revenue,
                'conversion_rate' => round($this->variant_conversion_rate * 100, 2) . '%',
                'revenue_per_visitor' => round($variantRPV, 2),
            ],
            'winner' => $this->winner,
            'confidence_level' => round($this->confidence_level * 100, 2) . '%',
            'improvement' => round($improvement, 2) . '%',
            'is_significant' => $this->confidence_level >= 0.95,
            'recommendation' => $this->getRecommendation(),
        ];
    }

    protected function getRecommendation()
    {
        if ($this->confidence_level < 0.95) {
            return "Continue experiment - not enough statistical significance yet.";
        }

        if ($this->winner === self::WINNER_VARIANT) {
            $variantRPV = $this->variant_revenue / max($this->variant_impressions, 1);
            $controlRPV = $this->control_revenue / max($this->control_impressions, 1);
            $improvement = (($variantRPV - $controlRPV) / max($controlRPV, 1)) * 100;
            return "Variant wins! Change price to \${$this->variant_price} for " . round($improvement, 1) . "% revenue improvement.";
        } elseif ($this->winner === self::WINNER_CONTROL) {
            return "Control wins! Keep current price of \${$this->control_price}.";
        }

        return "No clear winner. Consider running experiment longer or testing different price points.";
    }

    /**
     * Assign user to experiment group (A/B split)
     */
    public function getAssignedPrice($userId = null)
    {
        if ($this->status !== self::STATUS_ACTIVE) {
            return $this->control_price;
        }

        // Simple hash-based assignment for consistency
        $hash = $userId ? crc32($this->id . '-' . $userId) : rand();
        $isVariant = ($hash % 2) === 0;

        return $isVariant ? $this->variant_price : $this->control_price;
    }
}
