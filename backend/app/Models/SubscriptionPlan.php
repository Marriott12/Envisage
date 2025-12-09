<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubscriptionPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 
        'slug', 
        'description', 
        'monthly_price',
        'yearly_price',
        'features',
        'max_products',
        'max_featured_products',
        'commission_rate',
        'is_popular',
        'is_active'
    ];

    protected $casts = [
        'monthly_price' => 'decimal:2',
        'yearly_price' => 'decimal:2',
        'commission_rate' => 'decimal:2',
        'max_products' => 'integer',
        'max_featured_products' => 'integer',
        'features' => 'array',
        'is_popular' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class, 'subscription_plan_id');
    }

    public function activeSubscriptions()
    {
        return $this->hasMany(Subscription::class, 'subscription_plan_id')
            ->where('status', 'active');
    }
}
