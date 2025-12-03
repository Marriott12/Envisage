<?php

namespace App\Http\Controllers;

use App\Models\PromoCode;
use App\Models\ShippingAddress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CheckoutController extends Controller
{
    // Validate promo code
    public function validatePromoCode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string',
            'order_amount' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $promoCode = PromoCode::where('code', strtoupper($request->code))->first();

        if (!$promoCode) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid promo code.',
            ], 404);
        }

        $userId = $request->user() ? $request->user()->id : null;
        $validation = $promoCode->isValid($userId, $request->order_amount);

        if (!$validation['valid']) {
            return response()->json([
                'success' => false,
                'message' => $validation['message'],
            ], 422);
        }

        $discountAmount = $promoCode->calculateDiscount($request->order_amount);

        return response()->json([
            'success' => true,
            'message' => 'Promo code applied successfully!',
            'data' => [
                'promo_code_id' => $promoCode->id,
                'code' => $promoCode->code,
                'type' => $promoCode->type,
                'value' => $promoCode->value,
                'discount_amount' => $discountAmount,
                'new_total' => $request->order_amount - $discountAmount,
            ],
        ]);
    }

    // Apply promo code to order (called during order creation)
    public function applyPromoCode($promoCodeId, $userId, $orderId, $orderAmount)
    {
        $promoCode = PromoCode::find($promoCodeId);

        if (!$promoCode) {
            return null;
        }

        $validation = $promoCode->isValid($userId, $orderAmount);

        if (!$validation['valid']) {
            return null;
        }

        $discountAmount = $promoCode->calculateDiscount($orderAmount);
        $promoCode->incrementUsage($userId, $orderId, $discountAmount);

        return $discountAmount;
    }

    // Guest checkout - create session
    public function createGuestSession(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'shipping_address' => 'required|array',
            'cart_data' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $sessionToken = Str::random(64);

        $session = \App\Models\GuestCheckoutSession::create([
            'session_token' => $sessionToken,
            'email' => $request->email,
            'name' => $request->name,
            'phone' => $request->phone,
            'shipping_address' => json_encode($request->shipping_address),
            'cart_data' => json_encode($request->cart_data),
            'expires_at' => now()->addHours(24),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Guest checkout session created',
            'data' => [
                'session_token' => $sessionToken,
                'expires_at' => $session->expires_at,
            ],
        ]);
    }

    // Get guest checkout session
    public function getGuestSession(Request $request, $token)
    {
        $session = \App\Models\GuestCheckoutSession::where('session_token', $token)
            ->where('expires_at', '>', now())
            ->first();

        if (!$session) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired session',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'email' => $session->email,
                'name' => $session->name,
                'phone' => $session->phone,
                'shipping_address' => $session->shipping_address,
                'cart_data' => $session->cart_data,
            ],
        ]);
    }

    // Get shipping rates (placeholder - integrate with shipping provider)
    public function getShippingRates(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'address' => 'required|array',
            'cart_total' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        // Placeholder shipping rates - integrate with actual shipping API
        $rates = [
            [
                'id' => 'standard',
                'name' => 'Standard Shipping',
                'description' => '5-7 business days',
                'price' => 5.99,
            ],
            [
                'id' => 'express',
                'name' => 'Express Shipping',
                'description' => '2-3 business days',
                'price' => 12.99,
            ],
            [
                'id' => 'overnight',
                'name' => 'Overnight Shipping',
                'description' => 'Next business day',
                'price' => 24.99,
            ],
        ];

        // Free shipping for orders over $50
        if ($request->cart_total >= 50) {
            array_unshift($rates, [
                'id' => 'free',
                'name' => 'Free Shipping',
                'description' => '5-7 business days',
                'price' => 0,
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $rates,
        ]);
    }

    // Calculate order total
    public function calculateOrderTotal(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'subtotal' => 'required|numeric|min:0',
            'shipping_rate' => 'required|numeric|min:0',
            'promo_code' => 'nullable|string',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $subtotal = $request->subtotal;
        $shipping = $request->shipping_rate;
        $taxRate = $request->tax_rate ?? 0;

        // Apply promo code discount
        $discount = 0;
        $promoCodeData = null;

        if ($request->promo_code) {
            $promoCode = PromoCode::where('code', strtoupper($request->promo_code))->first();
            
            if ($promoCode) {
                $userId = $request->user() ? $request->user()->id : null;
                $validation = $promoCode->isValid($userId, $subtotal);
                
                if ($validation['valid']) {
                    $discount = $promoCode->calculateDiscount($subtotal);
                    $promoCodeData = [
                        'id' => $promoCode->id,
                        'code' => $promoCode->code,
                        'discount_amount' => $discount,
                    ];
                }
            }
        }

        // Calculate tax on (subtotal - discount)
        $taxableAmount = $subtotal - $discount;
        $tax = ($taxableAmount * $taxRate) / 100;

        // Total = subtotal - discount + shipping + tax
        $total = $subtotal - $discount + $shipping + $tax;

        return response()->json([
            'success' => true,
            'data' => [
                'subtotal' => round($subtotal, 2),
                'discount' => round($discount, 2),
                'shipping' => round($shipping, 2),
                'tax' => round($tax, 2),
                'total' => round($total, 2),
                'promo_code' => $promoCodeData,
            ],
        ]);
    }
}
