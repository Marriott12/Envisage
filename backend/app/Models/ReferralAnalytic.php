<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReferralAnalytic extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'date',
        'total_referrals',
        'pending_referrals',
        'registered_referrals',
        'converted_referrals',
        'link_clicks',
        'total_commission',
        'pending_commission',
        'paid_commission',
        'conversion_rate',
        'current_tier_id',
    ];

    protected $casts = [
        'date' => 'date',
        'total_commission' => 'decimal:2',
        'pending_commission' => 'decimal:2',
        'paid_commission' => 'decimal:2',
        'conversion_rate' => 'decimal:2',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function tier()
    {
        return $this->belongsTo(ReferralTier::class, 'current_tier_id');
    }

    // Helper methods
    public static function updateDailyStats($userId, $date = null)
    {
        $date = $date ?? today();

        // Count referrals
        $totalReferrals = Referral::where('referrer_id', $userId)->count();
        $pendingReferrals = Referral::where('referrer_id', $userId)->pending()->count();
        $registeredReferrals = Referral::where('referrer_id', $userId)->registered()->count();
        $convertedReferrals = Referral::where('referrer_id', $userId)->converted()->count();

        // Count link clicks
        $linkClicks = ReferralLink::where('user_id', $userId)->sum('clicks');

        // Calculate commissions
        $totalCommission = ReferralReward::where('referrer_id', $userId)->sum('amount');
        $pendingCommission = ReferralReward::where('referrer_id', $userId)->pending()->sum('amount');
        $paidCommission = ReferralReward::where('referrer_id', $userId)->paid()->sum('amount');

        // Calculate conversion rate
        $conversionRate = $linkClicks > 0 
            ? round(($convertedReferrals / $linkClicks) * 100, 2)
            : 0;

        // Get current tier
        $currentTier = ReferralTier::getTierForReferralCount($convertedReferrals);

        return self::updateOrCreate(
            [
                'user_id' => $userId,
                'date' => $date,
            ],
            [
                'total_referrals' => $totalReferrals,
                'pending_referrals' => $pendingReferrals,
                'registered_referrals' => $registeredReferrals,
                'converted_referrals' => $convertedReferrals,
                'link_clicks' => $linkClicks,
                'total_commission' => $totalCommission,
                'pending_commission' => $pendingCommission,
                'paid_commission' => $paidCommission,
                'conversion_rate' => $conversionRate,
                'current_tier_id' => $currentTier ? $currentTier->id : null,
            ]
        );
    }
}
