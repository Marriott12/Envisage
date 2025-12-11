<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoyaltyTier extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'min_points',
        'max_points',
        'multiplier',
        'discount_percentage',
        'color',
        'icon',
        'benefits',
        'bonuses',
        'is_active',
        'order',
    ];

    protected $casts = [
        'min_points' => 'integer',
        'max_points' => 'integer',
        'multiplier' => 'decimal:1',
        'discount_percentage' => 'integer',
        'benefits' => 'array',
        'bonuses' => 'array',
        'is_active' => 'boolean',
        'order' => 'integer',
    ];

    public function users()
    {
        return $this->hasMany(User::class, 'loyalty_tier_id');
    }

    public function getMemberCountAttribute()
    {
        return $this->users()->count();
    }
}
