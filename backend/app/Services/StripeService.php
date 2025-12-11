<?php

namespace App\Services;

use Stripe\Stripe;
use Stripe\PaymentIntent;
use Stripe\PaymentMethod as StripePaymentMethod;
use Stripe\Customer as StripeCustomer;
use Stripe\Refund as StripeRefund;
use App\Models\PaymentMethod;
use App\Models\User;
use App\Models\Order;

class StripeService
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    /**
     * Create a payment intent
     */
    public function createPaymentIntent($amount, $currency = 'usd', $metadata = [])
    {
        try {
            return PaymentIntent::create([
                'amount' => $amount * 100, // Convert to cents
                'currency' => $currency,
                'metadata' => $metadata,
                'automatic_payment_methods' => [
                    'enabled' => true,
                ],
            ]);
        } catch (\Exception $e) {
            throw new \Exception('Payment intent creation failed: ' . $e->getMessage());
        }
    }

    /**
     * Create payment intent with saved payment method
     */
    public function createPaymentIntentWithMethod($amount, $paymentMethodId, $customerId = null, $metadata = [])
    {
        try {
            $params = [
                'amount' => $amount * 100,
                'currency' => 'usd',
                'payment_method' => $paymentMethodId,
                'metadata' => $metadata,
                'confirm' => true,
                'return_url' => config('app.frontend_url') . '/orders/success',
            ];

            if ($customerId) {
                $params['customer'] = $customerId;
            }

            return PaymentIntent::create($params);
        } catch (\Exception $e) {
            throw new \Exception('Payment failed: ' . $e->getMessage());
        }
    }

    /**
     * Create or retrieve Stripe customer
     */
    public function createCustomer(User $user)
    {
        try {
            if ($user->stripe_customer_id) {
                return StripeCustomer::retrieve($user->stripe_customer_id);
            }

            $customer = StripeCustomer::create([
                'email' => $user->email,
                'name' => $user->name,
                'metadata' => [
                    'user_id' => $user->id,
                ],
            ]);

            $user->update(['stripe_customer_id' => $customer->id]);

            return $customer;
        } catch (\Exception $e) {
            throw new \Exception('Customer creation failed: ' . $e->getMessage());
        }
    }

    /**
     * Attach payment method to customer
     */
    public function attachPaymentMethod($paymentMethodId, $customerId)
    {
        try {
            $paymentMethod = StripePaymentMethod::retrieve($paymentMethodId);
            $paymentMethod->attach(['customer' => $customerId]);
            return $paymentMethod;
        } catch (\Exception $e) {
            throw new \Exception('Payment method attachment failed: ' . $e->getMessage());
        }
    }

    /**
     * Save payment method to database
     */
    public function savePaymentMethod(User $user, $stripePaymentMethod, $isDefault = false)
    {
        $data = [
            'user_id' => $user->id,
            'type' => $stripePaymentMethod->type,
            'provider' => 'stripe',
            'provider_payment_method_id' => $stripePaymentMethod->id,
            'is_default' => $isDefault,
        ];

        if ($stripePaymentMethod->type === 'card') {
            $data['last_four'] = $stripePaymentMethod->card->last4;
            $data['brand'] = $stripePaymentMethod->card->brand;
            $data['exp_month'] = $stripePaymentMethod->card->exp_month;
            $data['exp_year'] = $stripePaymentMethod->card->exp_year;
        }

        // If this is default, unset other defaults
        if ($isDefault) {
            PaymentMethod::where('user_id', $user->id)
                        ->update(['is_default' => false]);
        }

        return PaymentMethod::create($data);
    }

    /**
     * Process refund
     */
    public function processRefund($paymentIntentId, $amount = null)
    {
        try {
            $params = ['payment_intent' => $paymentIntentId];
            
            if ($amount) {
                $params['amount'] = $amount * 100; // Convert to cents
            }

            return StripeRefund::create($params);
        } catch (\Exception $e) {
            throw new \Exception('Refund failed: ' . $e->getMessage());
        }
    }

    /**
     * Split payment between card and loyalty points
     */
    public function createSplitPayment($user, $totalAmount, $pointsToUse, $paymentMethodId = null)
    {
        // Calculate point value (e.g., 100 points = $1)
        $pointValue = $pointsToUse / 100;
        $remainingAmount = max(0, $totalAmount - $pointValue);

        if ($remainingAmount > 0) {
            if (!$paymentMethodId) {
                throw new \Exception('Payment method required for remaining amount');
            }

            // Get Stripe customer
            $customer = $this->createCustomer($user);

            // Create payment intent for remaining amount
            $paymentIntent = $this->createPaymentIntentWithMethod(
                $remainingAmount,
                $paymentMethodId,
                $customer->id,
                [
                    'user_id' => $user->id,
                    'points_used' => $pointsToUse,
                    'point_value' => $pointValue,
                ]
            );

            return [
                'payment_intent_id' => $paymentIntent->id,
                'amount_charged' => $remainingAmount,
                'points_used' => $pointsToUse,
                'point_value' => $pointValue,
                'total_amount' => $totalAmount,
            ];
        }

        // Payment fully covered by points
        return [
            'payment_intent_id' => null,
            'amount_charged' => 0,
            'points_used' => $pointsToUse,
            'point_value' => $pointValue,
            'total_amount' => $totalAmount,
        ];
    }

    /**
     * Get payment method details
     */
    public function getPaymentMethod($paymentMethodId)
    {
        try {
            return StripePaymentMethod::retrieve($paymentMethodId);
        } catch (\Exception $e) {
            throw new \Exception('Payment method retrieval failed: ' . $e->getMessage());
        }
    }

    /**
     * Detach payment method
     */
    public function detachPaymentMethod($paymentMethodId)
    {
        try {
            $paymentMethod = StripePaymentMethod::retrieve($paymentMethodId);
            return $paymentMethod->detach();
        } catch (\Exception $e) {
            throw new \Exception('Payment method detachment failed: ' . $e->getMessage());
        }
    }
}
