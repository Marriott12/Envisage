<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    use HasFactory;

    protected $table = 'subscriptions';

    protected $fillable = [
        'user_id',
        'subscription_plan_id',
        'status',
        'billing_cycle',
        'start_date',
        'end_date',
        'next_billing_date',
        'cancelled_at',
        'trial_ends_at'
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'next_billing_date' => 'datetime',
        'cancelled_at' => 'datetime',
        'trial_ends_at' => 'datetime',
    ];

    /**
     * Get the user that owns the subscription
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the plan for this subscription
     */
    public function plan()
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }

    /**
     * Get subscription payments
     */
    public function payments()
    {
        return $this->hasMany(SubscriptionPayment::class);
    }

    /**
     * Scope to get active subscriptions
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Check if subscription is active
     */
    public function isActive()
    {
        return $this->status === 'active' && 
               $this->end_date && 
               $this->end_date->isFuture();
    }
}
