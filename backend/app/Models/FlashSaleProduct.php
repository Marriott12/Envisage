<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FlashSaleProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'flash_sale_id', 'product_id', 'original_price', 'sale_price',
        'discount_percentage', 'quantity_limit', 'quantity_sold',
        'per_user_limit', 'is_active'
    ];

    protected $casts = [
        'original_price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function flashSale()
    {
        return $this->belongsTo(FlashSale::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function purchases()
    {
        return $this->hasMany(FlashSalePurchase::class);
    }

    public function hasStock()
    {
        return $this->quantity_limit === null || 
               $this->quantity_sold < $this->quantity_limit;
    }

    public function userPurchaseCount($userId)
    {
        return $this->purchases()
            ->where('user_id', $userId)
            ->sum('quantity');
    }

    public function canUserPurchase($userId, $quantity = 1)
    {
        $purchased = $this->userPurchaseCount($userId);
        return ($purchased + $quantity) <= $this->per_user_limit;
    }
}
