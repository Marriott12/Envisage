<?php

namespace App\Http\Controllers;

use App\Services\ReferralService;
use App\Models\Referral;
use App\Models\ReferralTier;
use App\Models\ReferralLink;
use App\Models\ReferralReward;
use Illuminate\Http\Request;

class ReferralController extends Controller
{
    protected $referralService;

    public function __construct(ReferralService $referralService)
    {
        $this->referralService = $referralService;
    }

    /**
     * Get user's referral dashboard stats
     */
    public function getDashboard(Request $request)
    {
        $user = $request->user();
        $stats = $this->referralService->getUserStats($user->id);

        return response()->json([
            'stats' => $stats,
        ]);
    }

    /**
     * Get referral tiers
     */
    public function getTiers()
    {
        $tiers = ReferralTier::active()->orderBy('min_referrals')->get();

        return response()->json([
            'tiers' => $tiers,
        ]);
    }

    /**
     * Generate new referral link
     */
    public function generateLink(Request $request)
    {
        $validated = $request->validate([
            'campaign_name' => 'nullable|string|max:100',
            'utm_source' => 'nullable|string|max:50',
            'utm_medium' => 'nullable|string|max:50',
            'utm_campaign' => 'nullable|string|max:50',
        ]);

        $user = $request->user();

        $utmParams = [];
        if (isset($validated['utm_source'])) {
            $utmParams['utm_source'] = $validated['utm_source'];
        }
        if (isset($validated['utm_medium'])) {
            $utmParams['utm_medium'] = $validated['utm_medium'];
        }
        if (isset($validated['utm_campaign'])) {
            $utmParams['utm_campaign'] = $validated['utm_campaign'];
        }

        $link = $this->referralService->generateReferralLink(
            $user->id,
            $validated['campaign_name'] ?? null,
            $utmParams
        );

        return response()->json([
            'link' => $link,
            'message' => 'Referral link generated successfully',
        ], 201);
    }

    /**
     * Get user's referral links
     */
    public function getLinks(Request $request)
    {
        $user = $request->user();
        $links = ReferralLink::where('user_id', $user->id)->get();

        return response()->json([
            'links' => $links,
        ]);
    }

    /**
     * Track link click (public endpoint)
     */
    public function trackClick($token)
    {
        $link = $this->referralService->trackLinkClick($token);

        if (!$link) {
            return response()->json([
                'message' => 'Invalid or inactive link',
            ], 404);
        }

        return response()->json([
            'message' => 'Click tracked',
            'redirect_url' => config('app.frontend_url') . '/register?ref=' . $link->token,
        ]);
    }

    /**
     * Send referral invitation
     */
    public function sendInvitation(Request $request)
    {
        $validated = $request->validate([
            'referee_email' => 'required|email',
            'referee_name' => 'nullable|string|max:100',
            'source' => 'nullable|string|in:email,social,direct',
            'message' => 'nullable|string|max:500',
        ]);

        $user = $request->user();

        $referral = $this->referralService->createReferral(
            $user->id,
            $validated['referee_email'],
            $validated['referee_name'] ?? null,
            $validated['source'] ?? 'direct',
            ['custom_message' => $validated['message'] ?? null]
        );

        // TODO: Send invitation email
        // Mail::to($validated['referee_email'])->send(new ReferralInvitationMail($referral));

        return response()->json([
            'referral' => $referral,
            'message' => 'Invitation sent successfully',
        ], 201);
    }

    /**
     * Get user's referrals list
     */
    public function getReferrals(Request $request)
    {
        $user = $request->user();
        $status = $request->input('status');

        $query = Referral::where('referrer_id', $user->id)
            ->with(['referee', 'rewards', 'conversions']);

        if ($status) {
            $query->where('status', $status);
        }

        $referrals = $query->orderByDesc('created_at')->paginate(20);

        return response()->json($referrals);
    }

    /**
     * Get user's rewards
     */
    public function getRewards(Request $request)
    {
        $user = $request->user();
        $status = $request->input('status');

        $query = ReferralReward::where('referrer_id', $user->id)
            ->with(['referral', 'order']);

        if ($status) {
            $query->where('status', $status);
        }

        $rewards = $query->orderByDesc('earned_at')->paginate(20);

        return response()->json($rewards);
    }

    /**
     * Validate referral code (for registration)
     */
    public function validateCode($code)
    {
        $referral = Referral::where('referral_code', $code)
            ->active()
            ->with('referrer')
            ->first();

        if (!$referral) {
            return response()->json([
                'valid' => false,
                'message' => 'Invalid or expired referral code',
            ], 404);
        }

        return response()->json([
            'valid' => true,
            'referrer' => [
                'name' => $referral->referrer->name,
            ],
            'message' => 'Valid referral code',
        ]);
    }

    /**
     * Get leaderboard
     */
    public function getLeaderboard(Request $request)
    {
        $limit = $request->input('limit', 10);
        $leaderboard = $this->referralService->getLeaderboard($limit);

        return response()->json([
            'leaderboard' => $leaderboard,
        ]);
    }

    /**
     * Get program analytics (admin)
     */
    public function getAnalytics(Request $request)
    {
        $totalReferrals = Referral::count();
        $pendingReferrals = Referral::pending()->count();
        $registeredReferrals = Referral::registered()->count();
        $convertedReferrals = Referral::converted()->count();

        $totalRewards = ReferralReward::sum('amount');
        $pendingRewards = ReferralReward::pending()->sum('amount');
        $paidRewards = ReferralReward::paid()->sum('amount');

        $viralCoefficient = $this->referralService->calculateViralCoefficient();

        $avgDaysToConvert = Referral::converted()
            ->whereNotNull('registered_at')
            ->whereNotNull('converted_at')
            ->get()
            ->avg(function ($referral) {
                return $referral->daysToConvert();
            });

        return response()->json([
            'analytics' => [
                'total_referrals' => $totalReferrals,
                'pending_referrals' => $pendingReferrals,
                'registered_referrals' => $registeredReferrals,
                'converted_referrals' => $convertedReferrals,
                'conversion_rate' => $totalReferrals > 0 
                    ? round(($convertedReferrals / $totalReferrals) * 100, 2)
                    : 0,
                'total_rewards' => round($totalRewards, 2),
                'pending_rewards' => round($pendingRewards, 2),
                'paid_rewards' => round($paidRewards, 2),
                'viral_coefficient' => $viralCoefficient,
                'avg_days_to_convert' => round($avgDaysToConvert ?? 0, 1),
            ],
        ]);
    }

    /**
     * Update link status
     */
    public function updateLinkStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'is_active' => 'required|boolean',
        ]);

        $user = $request->user();
        $link = ReferralLink::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $link->update(['is_active' => $validated['is_active']]);

        return response()->json([
            'link' => $link,
            'message' => 'Link status updated',
        ]);
    }
}
