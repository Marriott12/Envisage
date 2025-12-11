<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Affiliate;
use App\Models\AffiliateConversion;
use Illuminate\Http\Request;

class AffiliateController extends Controller
{
    public function join(Request $request)
    {
        $request->validate([
            'payment_method' => 'nullable|string',
            'payment_details' => 'nullable|array',
        ]);

        // Check if user already an affiliate
        if (Affiliate::where('user_id', auth()->id())->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'You are already an affiliate',
            ], 400);
        }

        $affiliate = Affiliate::create([
            'user_id' => auth()->id(),
            'payment_method' => $request->payment_method,
            'payment_details' => $request->payment_details,
            'status' => 'active',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Successfully joined affiliate program',
            'data' => [
                'affiliate_code' => $affiliate->affiliate_code,
                'commission_rate' => $affiliate->commission_rate,
            ],
        ], 201);
    }

    public function dashboard()
    {
        $affiliate = Affiliate::where('user_id', auth()->id())->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => [
                'affiliate_code' => $affiliate->affiliate_code,
                'total_earnings' => $affiliate->total_earnings,
                'pending_earnings' => $affiliate->pending_earnings,
                'paid_earnings' => $affiliate->paid_earnings,
                'total_referrals' => $affiliate->total_referrals,
                'total_sales' => $affiliate->total_sales,
                'commission_rate' => $affiliate->commission_rate,
                'conversion_rate' => $affiliate->conversion_rate,
                'status' => $affiliate->status,
            ],
        ]);
    }

    public function conversions(Request $request)
    {
        $affiliate = Affiliate::where('user_id', auth()->id())->firstOrFail();

        $conversions = AffiliateConversion::with(['order', 'customer'])
            ->where('affiliate_id', $affiliate->id)
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);

        return response()->json([
            'success' => true,
            'data' => $conversions,
        ]);
    }

    public function stats(Request $request)
    {
        $affiliate = Affiliate::where('user_id', auth()->id())->firstOrFail();

        $stats = [
            'today' => [
                'conversions' => AffiliateConversion::where('affiliate_id', $affiliate->id)
                    ->whereDate('created_at', today())->count(),
                'earnings' => AffiliateConversion::where('affiliate_id', $affiliate->id)
                    ->whereDate('created_at', today())->sum('commission_amount'),
            ],
            'this_month' => [
                'conversions' => AffiliateConversion::where('affiliate_id', $affiliate->id)
                    ->whereMonth('created_at', now()->month)->count(),
                'earnings' => AffiliateConversion::where('affiliate_id', $affiliate->id)
                    ->whereMonth('created_at', now()->month)->sum('commission_amount'),
            ],
        ];

        return response()->json(['success' => true, 'data' => $stats]);
    }
}
