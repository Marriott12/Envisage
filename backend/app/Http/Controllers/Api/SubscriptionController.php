<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use App\Models\SellerSubscription;
use App\Models\SubscriptionPayment;
use App\Models\FeaturedProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use Stripe\Subscription;

class SubscriptionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum')->except(['plans', 'webhookHandler']);
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    public function plans()
    {
        $plans = SubscriptionPlan::where('is_active', true)
            ->orderBy('price')
            ->get();

        return response()->json(['plans' => $plans]);
    }

    public function currentSubscription()
    {
        $userId = Auth::id();
        
        $subscription = SellerSubscription::where('user_id', $userId)
            ->with('plan')
            ->latest()
            ->first();

        if (!$subscription || !$subscription->isActive()) {
            return response()->json([
                'subscription' => null,
                'has_active_subscription' => false,
            ]);
        }

        return response()->json([
            'subscription' => $subscription,
            'has_active_subscription' => true,
        ]);
    }

    public function subscribe(Request $request)
    {
        $request->validate([
            'plan_id' => 'required|exists:subscription_plans,id',
        ]);

        $userId = Auth::id();
        $plan = SubscriptionPlan::findOrFail($request->plan_id);

        // Check if user already has active subscription
        $existingSubscription = SellerSubscription::where('user_id', $userId)
            ->where('status', 'active')
            ->first();

        if ($existingSubscription) {
            return response()->json([
                'message' => 'You already have an active subscription',
            ], 400);
        }

        try {
            // Create Stripe Checkout Session
            $session = Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'usd',
                        'product_data' => [
                            'name' => $plan->name,
                            'description' => $plan->description,
                        ],
                        'unit_amount' => $plan->price * 100, // Convert to cents
                        'recurring' => [
                            'interval' => $plan->billing_cycle,
                        ],
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'subscription',
                'success_url' => config('app.frontend_url') . '/subscription/success?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => config('app.frontend_url') . '/subscription/plans',
                'client_reference_id' => $userId,
                'metadata' => [
                    'plan_id' => $plan->id,
                    'user_id' => $userId,
                ],
            ]);

            return response()->json([
                'checkout_url' => $session->url,
                'session_id' => $session->id,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create checkout session',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function cancel()
    {
        $userId = Auth::id();
        
        $subscription = SellerSubscription::where('user_id', $userId)
            ->where('status', 'active')
            ->firstOrFail();

        try {
            // Cancel Stripe subscription
            if ($subscription->stripe_subscription_id) {
                $stripeSubscription = Subscription::retrieve($subscription->stripe_subscription_id);
                $stripeSubscription->cancel();
            }

            $subscription->update([
                'status' => 'cancelled',
                'auto_renew' => false,
                'ends_at' => now(),
            ]);

            return response()->json([
                'message' => 'Subscription cancelled successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to cancel subscription',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function featureProduct(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'placement' => 'required|in:homepage,category,search',
            'starts_at' => 'required|date',
            'ends_at' => 'required|date|after:starts_at',
        ]);

        $userId = Auth::id();
        
        $subscription = SellerSubscription::where('user_id', $userId)
            ->where('status', 'active')
            ->with('plan')
            ->firstOrFail();

        // Check if plan allows featured products
        if (!$subscription->plan->featured_products_allowed) {
            return response()->json([
                'message' => 'Your subscription plan does not include featured products',
            ], 403);
        }

        // Check available featured slots
        $usedSlots = FeaturedProduct::where('user_id', $userId)
            ->where('ends_at', '>', now())
            ->count();

        if ($usedSlots >= $subscription->plan->featured_slots) {
            return response()->json([
                'message' => 'You have reached your featured product limit',
            ], 400);
        }

        $featured = FeaturedProduct::create([
            'user_id' => $userId,
            'product_id' => $request->product_id,
            'subscription_id' => $subscription->id,
            'placement' => $request->placement,
            'starts_at' => $request->starts_at,
            'ends_at' => $request->ends_at,
        ]);

        return response()->json(['featured' => $featured], 201);
    }

    public function webhookHandler(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $webhookSecret = config('services.stripe.webhook_secret');

        try {
            $event = \Stripe\Webhook::constructEvent($payload, $sigHeader, $webhookSecret);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Webhook signature verification failed'], 400);
        }

        switch ($event->type) {
            case 'checkout.session.completed':
                $this->handleCheckoutCompleted($event->data->object);
                break;

            case 'customer.subscription.updated':
                $this->handleSubscriptionUpdated($event->data->object);
                break;

            case 'customer.subscription.deleted':
                $this->handleSubscriptionDeleted($event->data->object);
                break;

            case 'invoice.payment_succeeded':
                $this->handlePaymentSucceeded($event->data->object);
                break;

            case 'invoice.payment_failed':
                $this->handlePaymentFailed($event->data->object);
                break;
        }

        return response()->json(['status' => 'success']);
    }

    protected function handleCheckoutCompleted($session)
    {
        $planId = $session->metadata->plan_id;
        $userId = $session->metadata->user_id;
        $plan = SubscriptionPlan::find($planId);

        $subscription = SellerSubscription::create([
            'user_id' => $userId,
            'plan_id' => $planId,
            'stripe_subscription_id' => $session->subscription,
            'status' => 'active',
            'starts_at' => now(),
            'ends_at' => now()->add($plan->billing_cycle, 1),
            'auto_renew' => true,
        ]);

        SubscriptionPayment::create([
            'subscription_id' => $subscription->id,
            'amount' => $plan->price,
            'stripe_payment_id' => $session->payment_intent,
            'status' => 'completed',
            'paid_at' => now(),
        ]);
    }

    protected function handleSubscriptionUpdated($stripeSubscription)
    {
        $subscription = SellerSubscription::where('stripe_subscription_id', $stripeSubscription->id)->first();
        
        if ($subscription) {
            $subscription->update([
                'status' => $stripeSubscription->status,
                'ends_at' => \Carbon\Carbon::createFromTimestamp($stripeSubscription->current_period_end),
            ]);
        }
    }

    protected function handleSubscriptionDeleted($stripeSubscription)
    {
        $subscription = SellerSubscription::where('stripe_subscription_id', $stripeSubscription->id)->first();
        
        if ($subscription) {
            $subscription->update([
                'status' => 'cancelled',
                'auto_renew' => false,
                'ends_at' => now(),
            ]);
        }
    }

    protected function handlePaymentSucceeded($invoice)
    {
        $subscription = SellerSubscription::where('stripe_subscription_id', $invoice->subscription)->first();
        
        if ($subscription) {
            SubscriptionPayment::create([
                'subscription_id' => $subscription->id,
                'amount' => $invoice->amount_paid / 100,
                'stripe_payment_id' => $invoice->payment_intent,
                'status' => 'completed',
                'paid_at' => now(),
            ]);
        }
    }

    protected function handlePaymentFailed($invoice)
    {
        $subscription = SellerSubscription::where('stripe_subscription_id', $invoice->subscription)->first();
        
        if ($subscription) {
            SubscriptionPayment::create([
                'subscription_id' => $subscription->id,
                'amount' => $invoice->amount_due / 100,
                'status' => 'failed',
            ]);

            // TODO: Send payment failed notification email
        }
    }
}
