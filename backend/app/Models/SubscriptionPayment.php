<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubscriptionPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'subscription_id', 'user_id', 'amount', 'currency',
        'stripe_payment_id', 'status', 'paid_at'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function subscription()
    {
        return $this->belongsTo(SellerSubscription::class, 'subscription_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
