<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserLoyaltyPoint;
use App\Models\LoyaltyTransaction;
use App\Models\Referral;
use App\Models\RewardsCatalog;
use App\Models\RewardRedemption;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoyaltyController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function myPoints()
    {
        $userId = Auth::id();
        
        $loyaltyPoints = UserLoyaltyPoint::firstOrCreate(
            ['user_id' => $userId],
            ['total_points' => 0, 'lifetime_points' => 0, 'tier' => 'bronze']
        );

        $recentTransactions = LoyaltyTransaction::where('user_id', $userId)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return response()->json([
            'loyalty' => $loyaltyPoints,
            'recent_transactions' => $recentTransactions,
        ]);
    }

    public function transactions(Request $request)
    {
        $userId = Auth::id();
        
        $query = LoyaltyTransaction::where('user_id', $userId);

        if ($request->type) {
            $query->where('type', $request->type);
        }

        if ($request->source) {
            $query->where('source', $request->source);
        }

        $transactions = $query->orderByDesc('created_at')
            ->paginate(20);

        return response()->json($transactions);
    }

    public function rewardsCatalog(Request $request)
    {
        $query = RewardsCatalog::where('is_active', true);

        if ($request->type) {
            $query->where('type', $request->type);
        }

        $rewards = $query->orderBy('points_cost')
            ->get();

        return response()->json(['rewards' => $rewards]);
    }

    public function redeemReward(Request $request)
    {
        $request->validate([
            'reward_id' => 'required|exists:rewards_catalog,id',
        ]);

        $userId = Auth::id();
        $reward = RewardsCatalog::findOrFail($request->reward_id);

        if (!$reward->isAvailable()) {
            return response()->json([
                'message' => 'This reward is not available',
            ], 400);
        }

        $loyaltyPoints = UserLoyaltyPoint::where('user_id', $userId)->first();

        if (!$loyaltyPoints || $loyaltyPoints->total_points < $reward->points_cost) {
            return response()->json([
                'message' => 'Insufficient points',
            ], 400);
        }

        // Deduct points
        if (!$loyaltyPoints->deductPoints($reward->points_cost, 'redemption', 'Redeemed: ' . $reward->name)) {
            return response()->json([
                'message' => 'Failed to deduct points',
            ], 500);
        }

        // Create redemption
        $redemption = RewardRedemption::create([
            'user_id' => $userId,
            'reward_id' => $reward->id,
            'points_used' => $reward->points_cost,
            'redemption_code' => RewardRedemption::generateRedemptionCode(),
            'expires_at' => now()->addDays(30),
        ]);

        // Update stock if applicable
        if ($reward->stock_quantity !== null) {
            $reward->decrement('stock_quantity');
        }

        // TODO: Send redemption email with code

        return response()->json([
            'redemption' => $redemption,
            'remaining_points' => $loyaltyPoints->total_points,
        ], 201);
    }

    public function myRedemptions()
    {
        $userId = Auth::id();
        
        $redemptions = RewardRedemption::where('user_id', $userId)
            ->with('reward')
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json($redemptions);
    }

    public function getReferralCode()
    {
        $userId = Auth::id();
        
        $referral = Referral::where('referrer_id', $userId)
            ->where('status', 'pending')
            ->first();

        if (!$referral) {
            $referral = Referral::create([
                'referrer_id' => $userId,
                'referral_code' => Referral::generateUniqueCode(),
                'status' => 'pending',
            ]);
        }

        $referrals = Referral::where('referrer_id', $userId)
            ->where('status', 'completed')
            ->count();

        $referralLink = config('app.frontend_url') . '/register?ref=' . $referral->referral_code;

        return response()->json([
            'referral_code' => $referral->referral_code,
            'referral_link' => $referralLink,
            'total_referrals' => $referrals,
        ]);
    }

    public function myReferrals()
    {
        $userId = Auth::id();
        
        $referrals = Referral::where('referrer_id', $userId)
            ->with('referred')
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json($referrals);
    }

    public function applyReferralCode(Request $request)
    {
        $request->validate([
            'referral_code' => 'required|string',
        ]);

        $userId = Auth::id();
        
        // Check if user already used a referral code
        $existingReferral = Referral::where('referred_id', $userId)->first();
        if ($existingReferral) {
            return response()->json([
                'message' => 'You have already used a referral code',
            ], 400);
        }

        $referral = Referral::where('referral_code', $request->referral_code)
            ->whereNull('referred_id')
            ->first();

        if (!$referral) {
            return response()->json([
                'message' => 'Invalid referral code',
            ], 404);
        }

        if ($referral->referrer_id === $userId) {
            return response()->json([
                'message' => 'You cannot use your own referral code',
            ], 400);
        }

        // Update referral
        $referral->update([
            'referred_id' => $userId,
            'status' => 'registered',
            'registered_at' => now(),
        ]);

        // Award points to both users
        $referrerPoints = UserLoyaltyPoint::firstOrCreate(
            ['user_id' => $referral->referrer_id],
            ['total_points' => 0, 'lifetime_points' => 0, 'tier' => 'bronze']
        );
        $referrerPoints->addPoints(500, 'referral', 'Referred a new user', null);

        $referredPoints = UserLoyaltyPoint::firstOrCreate(
            ['user_id' => $userId],
            ['total_points' => 0, 'lifetime_points' => 0, 'tier' => 'bronze']
        );
        $referredPoints->addPoints(200, 'referral', 'Used referral code', null);

        $referral->update(['points_earned' => 500]);

        return response()->json([
            'message' => 'Referral code applied successfully',
            'points_earned' => 200,
        ]);
    }
}
