<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GiftCard;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class GiftCardController extends Controller
{
    /**
     * Purchase a gift card
     */
    public function purchase(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:10|max:1000',
            'recipient_email' => 'required|email',
            'message' => 'nullable|string|max:500',
            'expires_at' => 'nullable|date|after:today',
        ]);

        // Generate unique code
        $code = strtoupper(Str::random(4) . '-' . Str::random(4) . '-' . Str::random(4));

        $giftCard = GiftCard::create([
            'code' => $code,
            'initial_amount' => $request->amount,
            'balance' => $request->amount,
            'purchased_by' => auth()->id(),
            'recipient_email' => $request->recipient_email,
            'message' => $request->message,
            'expires_at' => $request->expires_at,
            'status' => 'active',
        ]);

        // TODO: Send email to recipient

        return response()->json([
            'success' => true,
            'message' => 'Gift card purchased successfully',
            'data' => [
                'code' => $giftCard->code,
                'amount' => $giftCard->initial_amount,
                'recipient_email' => $giftCard->recipient_email,
            ],
        ], 201);
    }

    /**
     * Get user's gift cards
     */
    public function myCards()
    {
        $cards = GiftCard::where('purchased_by', auth()->id())
                        ->orWhere('used_by', auth()->id())
                        ->orderBy('created_at', 'desc')
                        ->get();

        return response()->json([
            'success' => true,
            'data' => $cards,
        ]);
    }

    /**
     * Validate gift card
     */
    public function validateCard(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        $giftCard = GiftCard::where('code', $request->code)->first();

        if (!$giftCard) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid gift card code',
            ], 404);
        }

        if (!$giftCard->isValid()) {
            return response()->json([
                'success' => false,
                'message' => 'Gift card is expired or has no balance',
            ], 400);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'code' => $giftCard->code,
                'balance' => $giftCard->balance,
                'initial_amount' => $giftCard->initial_amount,
                'expires_at' => $giftCard->expires_at,
            ],
        ]);
    }

    /**
     * Apply gift card to order
     */
    public function apply(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
            'amount_to_use' => 'required|numeric|min:0.01',
            'order_id' => 'required|exists:orders,id',
        ]);

        $giftCard = GiftCard::where('code', $request->code)->firstOrFail();

        if (!$giftCard->isValid()) {
            return response()->json([
                'success' => false,
                'message' => 'Gift card is not valid',
            ], 400);
        }

        $amountToUse = min($request->amount_to_use, $giftCard->balance);

        // Deduct from gift card balance
        $giftCard->balance -= $amountToUse;
        
        if ($giftCard->balance <= 0) {
            $giftCard->status = 'used';
        }

        if (!$giftCard->used_by) {
            $giftCard->used_by = auth()->id();
        }

        $giftCard->save();

        return response()->json([
            'success' => true,
            'message' => 'Gift card applied successfully',
            'data' => [
                'amount_used' => $amountToUse,
                'remaining_balance' => $giftCard->balance,
            ],
        ]);
    }
}
