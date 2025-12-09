<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductBundle extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'description', 'seller_id', 'image', 'discount_type',
        'discount_value', 'total_price', 'discounted_price',
        'is_active', 'starts_at', 'ends_at'
    ];

    protected $casts = [
        'discount_value' => 'decimal:2',
        'total_price' => 'decimal:2',
        'discounted_price' => 'decimal:2',
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function bundleProducts()
    {
        return $this->hasMany(BundleProduct::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'bundle_products')
            ->withPivot('quantity', 'price_at_time')
            ->withTimestamps();
    }

    public function calculatePrices()
    {
        $total = $this->bundleProducts()->sum('price_at_time');
        $this->total_price = $total;

        if ($this->discount_type === 'percentage') {
            $this->discounted_price = $total * (1 - ($this->discount_value / 100));
        } else {
            $this->discounted_price = $total - $this->discount_value;
        }

        $this->save();
    }

    public function isActive()
    {
        if (!$this->is_active) return false;
        if ($this->starts_at && $this->starts_at->isFuture()) return false;
        if ($this->ends_at && $this->ends_at->isPast()) return false;
        return true;
    }
}
