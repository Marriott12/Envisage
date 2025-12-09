<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FlashSale extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 
        'description', 
        'start_time',
        'end_time',
        'discount_percentage',
        'is_active',
        'total_quantity', 
        'sold_quantity', 
        'banner_image'
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'discount_percentage' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function products()
    {
        return $this->hasMany(FlashSaleProduct::class);
    }

    public function purchases()
    {
        return $this->hasMany(FlashSalePurchase::class);
    }

    public function isActive()
    {
        return $this->is_active && 
               $this->starts_at->isPast() && 
               $this->ends_at->isFuture();
    }

    public function hasStock()
    {
        return $this->total_quantity === null || 
               $this->sold_quantity < $this->total_quantity;
    }
}
