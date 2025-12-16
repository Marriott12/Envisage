<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Auction extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'seller_id',
        'starting_bid',
        'reserve_price',
        'current_bid',
        'buy_now_price',
        'bid_increment',
        'highest_bidder_id',
        'bid_count',
        'starts_at',
        'ends_at',
        'status',
        'featured',
        'views_count',
        'watchers_count',
    ];

    protected $casts = [
        'starting_bid' => 'decimal:2',
        'reserve_price' => 'decimal:2',
        'current_bid' => 'decimal:2',
        'buy_now_price' => 'decimal:2',
        'bid_increment' => 'decimal:2',
        'featured' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function highestBidder()
    {
        return $this->belongsTo(User::class, 'highest_bidder_id');
    }

    public function bids()
    {
        return $this->hasMany(AuctionBid::class)->orderBy('bid_amount', 'desc');
    }

    public function watchers()
    {
        return $this->belongsToMany(User::class, 'auction_watchers')
            ->withPivot('notify_outbid', 'notify_ending_soon')
            ->withTimestamps();
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>=', now());
    }

    public function scopeEnded($query)
    {
        return $query->where('ends_at', '<', now());
    }

    public function isActive()
    {
        return $this->status === 'active' && 
               $this->starts_at <= now() && 
               $this->ends_at >= now();
    }

    public function getTimeRemainingAttribute()
    {
        if (!$this->isActive()) {
            return 0;
        }
        return now()->diffInSeconds($this->ends_at);
    }

    public function getMinimumBidAttribute()
    {
        return $this->current_bid > 0 
            ? $this->current_bid + $this->bid_increment 
            : $this->starting_bid;
    }
}
