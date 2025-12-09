<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FlashSalePurchase extends Model
{
    use HasFactory;

    protected $fillable = [
        'flash_sale_id', 'flash_sale_product_id', 'user_id',
        'order_id', 'quantity', 'price_paid'
    ];

    protected $casts = [
        'price_paid' => 'decimal:2',
    ];

    public function flashSale()
    {
        return $this->belongsTo(FlashSale::class);
    }

    public function flashSaleProduct()
    {
        return $this->belongsTo(FlashSaleProduct::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
