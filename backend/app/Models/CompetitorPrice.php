<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompetitorPrice extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'competitor_name',
        'competitor_url',
        'competitor_price',
        'our_price',
        'price_difference',
        'price_diff_percentage',
        'product_match_quality',
        'in_stock',
        'scraped_at',
    ];

    protected $casts = [
        'competitor_price' => 'decimal:2',
        'our_price' => 'decimal:2',
        'price_difference' => 'decimal:2',
        'price_diff_percentage' => 'decimal:2',
        'in_stock' => 'boolean',
        'scraped_at' => 'datetime',
    ];

    // Match quality levels
    const QUALITY_HIGH = 'high';
    const QUALITY_MEDIUM = 'medium';
    const QUALITY_LOW = 'low';

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
    public function scopeForProduct($query, $productId)
    {
        return $query->where('product_id', $productId);
    }

    public function scopeInStock($query)
    {
        return $query->where('in_stock', true);
    }

    public function scopeHighQuality($query)
    {
        return $query->where('product_match_quality', self::QUALITY_HIGH);
    }

    public function scopeRecent($query, $hours = 24)
    {
        return $query->where('scraped_at', '>=', now()->subHours($hours));
    }

    public function scopeCheaper($query)
    {
        return $query->where('price_difference', '<', 0);
    }

    public function scopeMoreExpensive($query)
    {
        return $query->where('price_difference', '>', 0);
    }

    public function scopeByCompetitor($query, $competitorName)
    {
        return $query->where('competitor_name', $competitorName);
    }

    /**
     * Helper Methods
     */
    public static function recordPrice($productId, $competitorName, $competitorUrl, $competitorPrice, $ourPrice, $matchQuality = self::QUALITY_MEDIUM, $inStock = true)
    {
        $priceDifference = $ourPrice - $competitorPrice;
        $priceDiffPercentage = $competitorPrice > 0 ? (($ourPrice - $competitorPrice) / $competitorPrice) * 100 : 0;

        return static::updateOrCreate(
            [
                'product_id' => $productId,
                'competitor_name' => $competitorName,
            ],
            [
                'competitor_url' => $competitorUrl,
                'competitor_price' => $competitorPrice,
                'our_price' => $ourPrice,
                'price_difference' => round($priceDifference, 2),
                'price_diff_percentage' => round($priceDiffPercentage, 2),
                'product_match_quality' => $matchQuality,
                'in_stock' => $inStock,
                'scraped_at' => now(),
            ]
        );
    }

    public function isCompetitive()
    {
        // We are competitive if our price is within 5% of competitor's price
        return abs($this->price_diff_percentage) <= 5;
    }

    public function isCheaper()
    {
        return $this->our_price < $this->competitor_price;
    }

    public function isMoreExpensive()
    {
        return $this->our_price > $this->competitor_price;
    }

    public function getCompetitiveAdvantage()
    {
        if ($this->isCheaper()) {
            return [
                'status' => 'advantage',
                'message' => "We are {$this->price_diff_percentage}% cheaper",
                'amount' => abs($this->price_difference),
            ];
        } elseif ($this->isMoreExpensive()) {
            return [
                'status' => 'disadvantage',
                'message' => "We are {$this->price_diff_percentage}% more expensive",
                'amount' => abs($this->price_difference),
            ];
        } else {
            return [
                'status' => 'neutral',
                'message' => "Prices are equal",
                'amount' => 0,
            ];
        }
    }

    /**
     * Get average competitor price for a product
     */
    public static function getAverageCompetitorPrice($productId, $highQualityOnly = true)
    {
        $query = static::forProduct($productId)
            ->inStock()
            ->recent(48); // Last 48 hours

        if ($highQualityOnly) {
            $query->highQuality();
        }

        return $query->avg('competitor_price');
    }

    /**
     * Get lowest competitor price
     */
    public static function getLowestCompetitorPrice($productId, $highQualityOnly = true)
    {
        $query = static::forProduct($productId)
            ->inStock()
            ->recent(48);

        if ($highQualityOnly) {
            $query->highQuality();
        }

        $lowest = $query->orderBy('competitor_price')->first();
        return $lowest ? $lowest->competitor_price : null;
    }

    /**
     * Get competitive position summary
     */
    public static function getCompetitivePosition($productId)
    {
        $competitors = static::forProduct($productId)
            ->inStock()
            ->highQuality()
            ->recent(48)
            ->get();

        if ($competitors->isEmpty()) {
            return null;
        }

        $cheaper = $competitors->where('our_price', '<', $competitors->pluck('competitor_price')->first())->count();
        $moreExpensive = $competitors->where('our_price', '>', $competitors->pluck('competitor_price')->first())->count();
        $avgCompetitorPrice = $competitors->avg('competitor_price');
        $lowestCompetitorPrice = $competitors->min('competitor_price');
        $highestCompetitorPrice = $competitors->max('competitor_price');
        $ourPrice = $competitors->first()->our_price;

        return [
            'total_competitors' => $competitors->count(),
            'cheaper_than' => $cheaper,
            'more_expensive_than' => $moreExpensive,
            'our_price' => $ourPrice,
            'avg_competitor_price' => round($avgCompetitorPrice, 2),
            'lowest_competitor_price' => $lowestCompetitorPrice,
            'highest_competitor_price' => $highestCompetitorPrice,
            'price_position' => $ourPrice <= $avgCompetitorPrice ? 'competitive' : 'premium',
            'suggested_price' => round($avgCompetitorPrice * 0.98, 2), // 2% undercut
        ];
    }

    /**
     * Get competitor price trends
     */
    public static function getPriceTrends($productId, $days = 7)
    {
        $startDate = now()->subDays($days);

        $prices = static::forProduct($productId)
            ->where('scraped_at', '>=', $startDate)
            ->orderBy('scraped_at')
            ->get();

        if ($prices->isEmpty()) {
            return null;
        }

        $pricesByDate = $prices->groupBy(function ($item) {
            return $item->scraped_at->format('Y-m-d');
        })->map(function ($dayPrices) {
            return [
                'avg_competitor_price' => round($dayPrices->avg('competitor_price'), 2),
                'min_competitor_price' => $dayPrices->min('competitor_price'),
                'max_competitor_price' => $dayPrices->max('competitor_price'),
                'our_price' => $dayPrices->first()->our_price,
                'competitor_count' => $dayPrices->count(),
            ];
        });

        return $pricesByDate;
    }
}
