<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WishlistItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'wishlist_id',
        'product_id',
        'priority',
        'notes',
        'target_price',
        'price_alert_enabled',
    ];

    protected $casts = [
        'priority' => 'integer',
        'target_price' => 'decimal:2',
        'price_alert_enabled' => 'boolean',
    ];

    public function wishlist()
    {
        return $this->belongsTo(Wishlist::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
