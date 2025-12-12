<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductAnalytic extends Model
{
    use HasFactory;

    protected $fillable = [
        'date',
        'product_id',
        'views',
        'unique_views',
        'add_to_cart',
        'add_to_wishlist',
        'purchases',
        'revenue',
        'conversion_rate',
        'avg_time_on_page',
        'bounce_count',
    ];

    protected $casts = [
        'date' => 'date',
        'views' => 'integer',
        'unique_views' => 'integer',
        'add_to_cart' => 'integer',
        'add_to_wishlist' => 'integer',
        'purchases' => 'integer',
        'revenue' => 'decimal:2',
        'conversion_rate' => 'decimal:2',
        'avg_time_on_page' => 'decimal:2',
        'bounce_count' => 'integer',
    ];

    // Relationships
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // Scopes
    public function scopeByProduct($query, $productId)
    {
        return $query->where('product_id', $productId);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    // Helper Methods
    public static function recordAnalytics($date, $productId, $data)
    {
        return self::updateOrCreate(
            [
                'date' => $date,
                'product_id' => $productId,
            ],
            $data
        );
    }

    public function calculateConversionRate()
    {
        if ($this->views > 0) {
            $this->conversion_rate = round(($this->purchases / $this->views) * 100, 2);
            $this->save();
        }
    }
}
