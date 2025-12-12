<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ReferralTier extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'min_referrals',
        'max_referrals',
        'commission_rate',
        'bonus_amount',
        'benefits',
        'is_active',
    ];

    protected $casts = [
        'benefits' => 'array',
        'is_active' => 'boolean',
        'commission_rate' => 'decimal:2',
        'bonus_amount' => 'decimal:2',
    ];

    // Relationships
    public function users()
    {
        return $this->hasMany(User::class, 'referral_tier_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Helper methods
    public static function getTierForReferralCount($count)
    {
        return self::active()
            ->where('min_referrals', '<=', $count)
            ->where(function ($query) use ($count) {
                $query->whereNull('max_referrals')
                    ->orWhere('max_referrals', '>=', $count);
            })
            ->orderByDesc('min_referrals')
            ->first();
    }

    public function isInRange($count)
    {
        return $count >= $this->min_referrals 
            && ($this->max_referrals === null || $count <= $this->max_referrals);
    }
}
