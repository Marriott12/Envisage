<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeaturedProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id', 'user_id', 'subscription_id', 'placement',
        'starts_at', 'ends_at', 'is_active', 'clicks', 'impressions'
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function subscription()
    {
        return $this->belongsTo(SellerSubscription::class, 'subscription_id');
    }

    public function incrementClicks()
    {
        $this->increment('clicks');
    }

    public function incrementImpressions()
    {
        $this->increment('impressions');
    }
}
