<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\StripeService;
use App\Models\PaymentMethod;
use App\Models\Order;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    protected $stripeService;

    public function __construct(StripeService $stripeService)
    {
        $this->stripeService = $stripeService;
    }

    /**
     * Create payment intent
     */
    public function createIntent(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'nullable|string|size:3',
            'order_id' => 'nullable|exists:orders,id',
        ]);

        try {
            $metadata = [];
            if ($request->order_id) {
                $metadata['order_id'] = $request->order_id;
            }

            $paymentIntent = $this->stripeService->createPaymentIntent(
                $request->amount,
                $request->currency ?? 'usd',
                $metadata
            );

            return response()->json([
                'success' => true,
                'client_secret' => $paymentIntent->client_secret,
                'payment_intent_id' => $paymentIntent->id,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get user's saved payment methods
     */
    public function getPaymentMethods(Request $request)
    {
        $methods = PaymentMethod::where('user_id', auth()->id())
            ->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function($method) {
                return [
                    'id' => $method->id,
                    'type' => $method->type,
                    'provider' => $method->provider,
                    'display_name' => $method->display_name,
                    'last_four' => $method->last_four,
                    'brand' => $method->brand,
                    'exp_month' => $method->exp_month,
                    'exp_year' => $method->exp_year,
                    'is_default' => $method->is_default,
                    'is_expired' => $method->is_expired,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $methods,
        ]);
    }

    /**
     * Save a new payment method
     */
    public function savePaymentMethod(Request $request)
    {
        $request->validate([
            'payment_method_id' => 'required|string',
            'is_default' => 'nullable|boolean',
        ]);

        try {
            $user = auth()->user();

            // Create or get Stripe customer
            $customer = $this->stripeService->createCustomer($user);

            // Attach payment method to customer
            $stripePaymentMethod = $this->stripeService->attachPaymentMethod(
                $request->payment_method_id,
                $customer->id
            );

            // Save to database
            $paymentMethod = $this->stripeService->savePaymentMethod(
                $user,
                $stripePaymentMethod,
                $request->is_default ?? false
            );

            return response()->json([
                'success' => true,
                'message' => 'Payment method saved successfully',
                'data' => [
                    'id' => $paymentMethod->id,
                    'display_name' => $paymentMethod->display_name,
                    'is_default' => $paymentMethod->is_default,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete payment method
     */
    public function deletePaymentMethod(Request $request, $id)
    {
        try {
            $method = PaymentMethod::where('user_id', auth()->id())
                ->findOrFail($id);

            // Detach from Stripe
            $this->stripeService->detachPaymentMethod($method->provider_payment_method_id);

            $method->delete();

            return response()->json([
                'success' => true,
                'message' => 'Payment method deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Set default payment method
     */
    public function setDefaultPaymentMethod(Request $request, $id)
    {
        try {
            // Unset all defaults
            PaymentMethod::where('user_id', auth()->id())
                ->update(['is_default' => false]);

            // Set new default
            $method = PaymentMethod::where('user_id', auth()->id())
                ->findOrFail($id);
            
            $method->update(['is_default' => true]);

            return response()->json([
                'success' => true,
                'message' => 'Default payment method updated',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Process payment with split (points + card)
     */
    public function processSplitPayment(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'points_to_use' => 'required|integer|min:0',
            'payment_method_id' => 'nullable|string',
            'order_id' => 'required|exists:orders,id',
        ]);

        try {
            $user = auth()->user();
            
            // Verify user has enough points
            $userPoints = $user->loyaltyPoints()->sum('points');
            if ($request->points_to_use > $userPoints) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient loyalty points',
                ], 400);
            }

            $result = $this->stripeService->createSplitPayment(
                $user,
                $request->amount,
                $request->points_to_use,
                $request->payment_method_id
            );

            // Deduct points if used
            if ($result['points_used'] > 0) {
                $user->loyaltyPoints()->create([
                    'points' => -$result['points_used'],
                    'type' => 'redeemed',
                    'description' => 'Redeemed for order payment',
                    'order_id' => $request->order_id,
                ]);
            }

            // Update order
            $order = Order::findOrFail($request->order_id);
            $order->update([
                'payment_status' => 'paid',
                'payment_method' => 'stripe_split',
                'transaction_id' => $result['payment_intent_id'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Payment processed successfully',
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Webhook handler for Stripe events
     */
    public function webhook(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $webhookSecret = config('services.stripe.webhook_secret');

        try {
            $event = \Stripe\Webhook::constructEvent(
                $payload,
                $sigHeader,
                $webhookSecret
            );

            // Handle the event
            switch ($event->type) {
                case 'payment_intent.succeeded':
                    $paymentIntent = $event->data->object;
                    $this->handlePaymentIntentSucceeded($paymentIntent);
                    break;

                case 'payment_intent.payment_failed':
                    $paymentIntent = $event->data->object;
                    $this->handlePaymentIntentFailed($paymentIntent);
                    break;

                case 'charge.refunded':
                    $charge = $event->data->object;
                    $this->handleChargeRefunded($charge);
                    break;

                default:
                    // Unhandled event type
            }

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    private function handlePaymentIntentSucceeded($paymentIntent)
    {
        if (isset($paymentIntent->metadata->order_id)) {
            $order = Order::find($paymentIntent->metadata->order_id);
            if ($order) {
                $order->update([
                    'payment_status' => 'paid',
                    'transaction_id' => $paymentIntent->id,
                ]);
            }
        }
    }

    private function handlePaymentIntentFailed($paymentIntent)
    {
        if (isset($paymentIntent->metadata->order_id)) {
            $order = Order::find($paymentIntent->metadata->order_id);
            if ($order) {
                $order->update([
                    'payment_status' => 'failed',
                ]);
            }
        }
    }

    private function handleChargeRefunded($charge)
    {
        // Update refund status in database
        if (isset($charge->metadata->order_id)) {
            $order = Order::find($charge->metadata->order_id);
            if ($order) {
                $order->update([
                    'payment_status' => 'refunded',
                ]);
            }
        }
    }
}
