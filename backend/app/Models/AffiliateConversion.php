<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AffiliateConversion extends Model
{
    use HasFactory;

    protected $fillable = [
        'affiliate_id',
        'order_id',
        'customer_id',
        'order_amount',
        'commission_amount',
        'commission_rate',
        'status',
        'approved_at',
        'paid_at',
    ];

    protected $casts = [
        'order_amount' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'commission_rate' => 'decimal:2',
        'approved_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    public function affiliate()
    {
        return $this->belongsTo(Affiliate::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }
}
