<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /**
     * Test creating payment intent
     */
    public function test_can_create_payment_intent()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'total_amount' => 100.00,
            'status' => 'pending'
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/payment/intent', [
                'order_id' => $order->id
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'client_secret',
                'publishable_key'
            ]);
    }

    /**
     * Test confirming payment
     */
    public function test_can_confirm_payment()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'total_amount' => 100.00,
            'status' => 'pending'
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/payment/confirm', [
                'order_id' => $order->id,
                'payment_intent_id' => 'pi_test_123456'
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Payment confirmed successfully'
            ]);

        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'status' => 'succeeded'
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'paid'
        ]);
    }

    /**
     * Test payment requires authentication
     */
    public function test_payment_requires_authentication()
    {
        $response = $this->postJson('/api/payment/intent', [
            'order_id' => 1
        ]);

        $response->assertStatus(401);
    }

    /**
     * Test refund payment
     */
    public function test_admin_can_refund_payment()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $token = $admin->createToken('test-token')->plainTextToken;

        $payment = Payment::factory()->create([
            'stripe_payment_intent_id' => 'pi_test_123456',
            'amount' => 100.00,
            'status' => 'succeeded'
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/admin/payment/refund', [
                'payment_id' => $payment->id,
                'amount' => 100.00
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Payment refunded successfully'
            ]);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'refunded'
        ]);
    }

    /**
     * Test regular user cannot refund
     */
    public function test_regular_user_cannot_refund_payment()
    {
        $user = User::factory()->create(['role' => 'user']);
        $token = $user->createToken('test-token')->plainTextToken;

        $payment = Payment::factory()->create();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/admin/payment/refund', [
                'payment_id' => $payment->id,
                'amount' => 100.00
            ]);

        $response->assertStatus(403);
    }

    /**
     * Test webhook handling
     */
    public function test_webhook_handles_payment_succeeded()
    {
        $order = Order::factory()->create([
            'total_amount' => 100.00,
            'status' => 'pending'
        ]);

        $payload = [
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => [
                    'id' => 'pi_test_123456',
                    'amount' => 10000, // Stripe uses cents
                    'metadata' => [
                        'order_id' => $order->id
                    ]
                ]
            ]
        ];

        $response = $this->postJson('/api/webhook/stripe', $payload);

        $response->assertStatus(200);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'paid'
        ]);
    }

    /**
     * Test payment validation
     */
    public function test_payment_intent_requires_order_id()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/payment/intent', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['order_id']);
    }

    /**
     * Test user can only pay for own orders
     */
    public function test_user_cannot_pay_for_others_orders()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $token = $user1->createToken('test-token')->plainTextToken;

        $order = Order::factory()->create([
            'user_id' => $user2->id,
            'total_amount' => 100.00
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/payment/intent', [
                'order_id' => $order->id
            ]);

        $response->assertStatus(403);
    }
}

