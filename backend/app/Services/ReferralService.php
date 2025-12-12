<?php

namespace App\Services;

use App\Models\Referral;
use App\Models\ReferralTier;
use App\Models\ReferralLink;
use App\Models\ReferralReward;
use App\Models\ReferralConversion;
use App\Models\ReferralAnalytic;
use App\Models\User;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

class ReferralService
{
    /**
     * Create a referral invitation
     */
    public function createReferral($referrerId, $refereeEmail, $refereeName = null, $source = 'direct', $metadata = [])
    {
        // Check if email already referred by this user
        $existing = Referral::where('referrer_id', $referrerId)
            ->where('referee_email', $refereeEmail)
            ->active()
            ->first();

        if ($existing) {
            return $existing;
        }

        return Referral::create([
            'referrer_id' => $referrerId,
            'referee_email' => $refereeEmail,
            'referee_name' => $refereeName,
            'status' => Referral::STATUS_PENDING,
            'source' => $source,
            'metadata' => $metadata,
            'expires_at' => now()->addDays(90),
        ]);
    }

    /**
     * Generate referral link for user
     */
    public function generateReferralLink($userId, $campaignName = null, $utmParams = [])
    {
        return ReferralLink::create([
            'user_id' => $userId,
            'campaign_name' => $campaignName,
            'utm_params' => $utmParams,
        ]);
    }

    /**
     * Track referral link click
     */
    public function trackLinkClick($token)
    {
        $link = ReferralLink::where('token', $token)->first();

        if ($link && $link->is_active) {
            $link->trackClick();
            return $link;
        }

        return null;
    }

    /**
     * Register referee (when they sign up)
     */
    public function registerReferee($referralCode, $userId)
    {
        $referral = Referral::where('referral_code', $referralCode)
            ->active()
            ->first();

        if (!$referral) {
            return null;
        }

        $referral->markAsRegistered($userId);

        // Track link conversion
        $link = ReferralLink::where('user_id', $referral->referrer_id)->first();
        if ($link) {
            $link->trackConversion();
        }

        return $referral;
    }

    /**
     * Process referral conversion (when referee makes first purchase)
     */
    public function processConversion($userId, $orderId)
    {
        // Find referral for this user
        $referral = Referral::where('referee_id', $userId)
            ->whereIn('status', [Referral::STATUS_REGISTERED, Referral::STATUS_PENDING])
            ->first();

        if (!$referral) {
            return null;
        }

        $order = Order::find($orderId);
        if (!$order || $order->status !== 'completed') {
            return null;
        }

        // Check if this is first purchase
        $isFirstPurchase = Order::where('user_id', $userId)
            ->where('status', 'completed')
            ->count() === 1;

        if (!$isFirstPurchase) {
            return null; // Only first purchase gets commission
        }

        // Get referrer's current tier
        $convertedCount = Referral::where('referrer_id', $referral->referrer_id)
            ->converted()
            ->count();

        $tier = ReferralTier::getTierForReferralCount($convertedCount);
        $commissionRate = $tier ? $tier->commission_rate : 5.00;

        // Calculate commission
        $commissionAmount = ($order->total * $commissionRate) / 100;

        // Record conversion
        $conversion = ReferralConversion::recordConversion(
            $referral->id,
            $orderId,
            $order->total,
            $commissionRate,
            true
        );

        // Create commission reward
        $reward = ReferralReward::createCommission(
            $referral->referrer_id,
            $referral->id,
            $orderId,
            $commissionAmount,
            $commissionRate
        );

        // Mark referral as converted
        $referral->markAsConverted();

        // Check for tier upgrade bonus
        $this->checkTierUpgrade($referral->referrer_id);

        // Update analytics
        ReferralAnalytic::updateDailyStats($referral->referrer_id);

        return [
            'referral' => $referral,
            'conversion' => $conversion,
            'reward' => $reward,
            'tier' => $tier,
        ];
    }

    /**
     * Check if user qualified for tier upgrade bonus
     */
    public function checkTierUpgrade($userId)
    {
        $convertedCount = Referral::where('referrer_id', $userId)
            ->converted()
            ->count();

        $newTier = ReferralTier::getTierForReferralCount($convertedCount);

        if (!$newTier) {
            return null;
        }

        // Check if just reached this tier
        $previousCount = $convertedCount - 1;
        $previousTier = ReferralTier::getTierForReferralCount($previousCount);

        if ($newTier->id !== ($previousTier ? $previousTier->id : null) && $newTier->bonus_amount > 0) {
            // User just upgraded, give bonus
            $referral = Referral::where('referrer_id', $userId)
                ->converted()
                ->latest()
                ->first();

            if ($referral) {
                return ReferralReward::createBonus(
                    $userId,
                    $referral->id,
                    $newTier->bonus_amount,
                    "Tier upgrade bonus: Welcome to {$newTier->name} tier!"
                );
            }
        }

        return null;
    }

    /**
     * Get user's referral stats
     */
    public function getUserStats($userId)
    {
        $totalReferrals = Referral::where('referrer_id', $userId)->count();
        $pendingReferrals = Referral::where('referrer_id', $userId)->pending()->count();
        $registeredReferrals = Referral::where('referrer_id', $userId)->registered()->count();
        $convertedReferrals = Referral::where('referrer_id', $userId)->converted()->count();

        $totalCommission = ReferralReward::where('referrer_id', $userId)->sum('amount');
        $pendingCommission = ReferralReward::where('referrer_id', $userId)->pending()->sum('amount');
        $paidCommission = ReferralReward::where('referrer_id', $userId)->paid()->sum('amount');

        $currentTier = ReferralTier::getTierForReferralCount($convertedReferrals);
        $nextTier = ReferralTier::active()
            ->where('min_referrals', '>', $convertedReferrals)
            ->orderBy('min_referrals')
            ->first();

        $referralsToNextTier = $nextTier ? ($nextTier->min_referrals - $convertedReferrals) : 0;

        $links = ReferralLink::where('user_id', $userId)->get();
        $totalClicks = $links->sum('clicks');
        $totalLinkConversions = $links->sum('conversions');

        $conversionRate = $totalClicks > 0 
            ? round(($convertedReferrals / $totalClicks) * 100, 2)
            : 0;

        return [
            'total_referrals' => $totalReferrals,
            'pending_referrals' => $pendingReferrals,
            'registered_referrals' => $registeredReferrals,
            'converted_referrals' => $convertedReferrals,
            'total_commission' => round($totalCommission, 2),
            'pending_commission' => round($pendingCommission, 2),
            'paid_commission' => round($paidCommission, 2),
            'current_tier' => $currentTier,
            'next_tier' => $nextTier,
            'referrals_to_next_tier' => $referralsToNextTier,
            'total_clicks' => $totalClicks,
            'conversion_rate' => $conversionRate,
            'links' => $links,
        ];
    }

    /**
     * Get leaderboard (top referrers)
     */
    public function getLeaderboard($limit = 10)
    {
        return DB::table('users')
            ->join('referrals', 'users.id', '=', 'referrals.referrer_id')
            ->where('referrals.status', Referral::STATUS_CONVERTED)
            ->select(
                'users.id',
                'users.name',
                DB::raw('COUNT(referrals.id) as total_conversions'),
                DB::raw('(SELECT SUM(amount) FROM referral_rewards WHERE referrer_id = users.id AND status = "paid") as total_earnings')
            )
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('total_conversions')
            ->limit($limit)
            ->get();
    }

    /**
     * Calculate viral coefficient (K-factor)
     * K > 1 = viral growth
     */
    public function calculateViralCoefficient()
    {
        $totalUsers = User::count();
        $totalReferrals = Referral::converted()->count();

        if ($totalUsers == 0) {
            return 0;
        }

        // K = (invites per user) × (conversion rate)
        $invitesPerUser = Referral::count() / $totalUsers;
        $conversionRate = Referral::count() > 0 
            ? $totalReferrals / Referral::count()
            : 0;

        $kFactor = $invitesPerUser * $conversionRate;

        return round($kFactor, 2);
    }

    /**
     * Expire old referrals
     */
    public function expireOldReferrals()
    {
        return Referral::where('expires_at', '<', now())
            ->whereIn('status', [Referral::STATUS_PENDING, Referral::STATUS_REGISTERED])
            ->update(['status' => Referral::STATUS_EXPIRED]);
    }

    /**
     * Approve pending rewards
     */
    public function approvePendingRewards($daysOld = 7)
    {
        // Auto-approve rewards after X days (fraud prevention window)
        $rewards = ReferralReward::pending()
            ->where('earned_at', '<=', now()->subDays($daysOld))
            ->get();

        foreach ($rewards as $reward) {
            $reward->approve();
        }

        return $rewards->count();
    }
}
