<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AbandonedCart;
use App\Models\CartRecoveryEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AbandonedCartController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum')->except(['recover']);
    }

    public function track(Request $request)
    {
        $request->validate([
            'cart_data' => 'required|array',
            'total_value' => 'required|numeric|min:0',
        ]);

        $userId = Auth::id();

        $abandonedCart = AbandonedCart::updateOrCreate(
            [
                'user_id' => $userId,
                'recovered' => false,
            ],
            [
                'cart_data' => $request->cart_data,
                'total_value' => $request->total_value,
                'recovery_token' => AbandonedCart::generateRecoveryToken(),
                'recovery_email_count' => 0,
            ]
        );

        return response()->json([
            'message' => 'Cart tracked',
            'cart_id' => $abandonedCart->id,
        ]);
    }

    public function list()
    {
        // Admin only
        $carts = AbandonedCart::where('recovered', false)
            ->where('created_at', '>=', now()->subDays(7))
            ->with('user')
            ->orderByDesc('total_value')
            ->paginate(50);

        return response()->json($carts);
    }

    public function recover($token)
    {
        $cart = AbandonedCart::where('recovery_token', $token)
            ->where('recovered', false)
            ->firstOrFail();

        $cart->update([
            'recovered' => true,
            'recovered_at' => now(),
        ]);

        return response()->json([
            'message' => 'Cart recovered',
            'cart_data' => $cart->cart_data,
        ]);
    }

    public function trackEmailOpen($emailId)
    {
        $email = CartRecoveryEmail::findOrFail($emailId);

        if (!$email->was_opened) {
            $email->update([
                'was_opened' => true,
                'opened_at' => now(),
            ]);
        }

        return response()->noContent();
    }

    public function trackEmailClick($emailId)
    {
        $email = CartRecoveryEmail::findOrFail($emailId);

        if (!$email->was_clicked) {
            $email->update([
                'was_clicked' => true,
                'clicked_at' => now(),
            ]);
        }

        // Redirect to recovery page
        $cart = AbandonedCart::findOrFail($email->abandoned_cart_id);
        return redirect(config('app.frontend_url') . '/cart/recover/' . $cart->recovery_token);
    }

    public function stats()
    {
        // Admin only
        $totalAbandoned = AbandonedCart::where('created_at', '>=', now()->subDays(30))->count();
        $totalRecovered = AbandonedCart::where('recovered', true)
            ->where('created_at', '>=', now()->subDays(30))
            ->count();
        
        $totalValue = AbandonedCart::where('created_at', '>=', now()->subDays(30))
            ->sum('total_value');
        
        $recoveredValue = AbandonedCart::where('recovered', true)
            ->where('created_at', '>=', now()->subDays(30))
            ->sum('total_value');

        $emailsSent = CartRecoveryEmail::where('created_at', '>=', now()->subDays(30))->count();
        $emailsOpened = CartRecoveryEmail::where('was_opened', true)
            ->where('created_at', '>=', now()->subDays(30))
            ->count();
        $emailsClicked = CartRecoveryEmail::where('was_clicked', true)
            ->where('created_at', '>=', now()->subDays(30))
            ->count();

        return response()->json([
            'total_abandoned' => $totalAbandoned,
            'total_recovered' => $totalRecovered,
            'recovery_rate' => $totalAbandoned > 0 ? ($totalRecovered / $totalAbandoned) * 100 : 0,
            'total_value' => $totalValue,
            'recovered_value' => $recoveredValue,
            'email_stats' => [
                'sent' => $emailsSent,
                'opened' => $emailsOpened,
                'clicked' => $emailsClicked,
                'open_rate' => $emailsSent > 0 ? ($emailsOpened / $emailsSent) * 100 : 0,
                'click_rate' => $emailsSent > 0 ? ($emailsClicked / $emailsSent) * 100 : 0,
            ],
        ]);
    }
}
