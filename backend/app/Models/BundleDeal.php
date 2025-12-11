<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BundleDeal extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'regular_price',
        'bundle_price',
        'discount_percentage',
        'stock',
        'is_active',
        'starts_at',
        'ends_at',
    ];

    protected $casts = [
        'regular_price' => 'decimal:2',
        'bundle_price' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'stock' => 'integer',
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public function products()
    {
        return $this->belongsToMany(Product::class, 'bundle_products')
            ->withPivot('quantity')
            ->withTimestamps();
    }

    public function bundleProducts()
    {
        return $this->hasMany(BundleProduct::class, 'bundle_id');
    }

    public function isActive()
    {
        return $this->is_active && 
               (!$this->starts_at || $this->starts_at->isPast()) && 
               (!$this->ends_at || $this->ends_at->isFuture()) &&
               $this->stock > 0;
    }

    public function getSavings()
    {
        return $this->regular_price - $this->bundle_price;
    }
}
