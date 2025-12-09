<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SellerSubscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'plan_id', 'status', 'starts_at', 'ends_at',
        'trial_ends_at', 'cancelled_at', 'stripe_subscription_id', 'auto_renew'
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'trial_ends_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'auto_renew' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function plan()
    {
        return $this->belongsTo(SubscriptionPlan::class, 'plan_id');
    }

    public function payments()
    {
        return $this->hasMany(SubscriptionPayment::class, 'subscription_id');
    }

    public function isActive()
    {
        return $this->status === 'active' && 
               (!$this->ends_at || $this->ends_at->isFuture());
    }

    public function isOnTrial()
    {
        return $this->status === 'trial' && 
               $this->trial_ends_at && 
               $this->trial_ends_at->isFuture();
    }
}
