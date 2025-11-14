<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Cart;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /**
     * Test creating an order from cart
     */
    public function test_user_can_create_order_from_cart()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;
        
        $product = Product::factory()->create([
            'price' => 50.00,
            'stock' => 10
        ]);

        Cart::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 2
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/orders', [
                'shipping_address' => $this->faker->address,
                'payment_method' => 'stripe'
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'order' => ['id', 'total_amount', 'status']
            ]);

        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'total_amount' => 100.00, // 2 * 50
            'status' => 'pending'
        ]);

        $this->assertDatabaseHas('order_items', [
            'product_id' => $product->id,
            'quantity' => 2,
            'price' => 50.00
        ]);

        // Cart should be cleared after order
        $this->assertDatabaseCount('carts', 0);
    }

    /**
     * Test viewing user's orders
     */
    public function test_user_can_view_own_orders()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        Order::factory()->count(3)->create(['user_id' => $user->id]);
        Order::factory()->count(2)->create(); // Other user's orders

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/orders');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    /**
     * Test viewing single order details
     */
    public function test_user_can_view_order_details()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $order = Order::factory()->create(['user_id' => $user->id]);
        $product = Product::factory()->create(['price' => 25.00]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 3,
            'price' => 25.00
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/orders/' . $order->id);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'order' => [
                    'id',
                    'total_amount',
                    'status',
                    'items' => [
                        '*' => ['product_id', 'quantity', 'price']
                    ]
                ]
            ]);
    }

    /**
     * Test user cannot view others' orders
     */
    public function test_user_cannot_view_others_orders()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $token = $user1->createToken('test-token')->plainTextToken;

        $order = Order::factory()->create(['user_id' => $user2->id]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/orders/' . $order->id);

        $response->assertStatus(403);
    }

    /**
     * Test admin can update order status
     */
    public function test_admin_can_update_order_status()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $token = $admin->createToken('test-token')->plainTextToken;

        $order = Order::factory()->create(['status' => 'pending']);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/admin/orders/' . $order->id . '/status', [
                'status' => 'processing'
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'processing'
        ]);
    }

    /**
     * Test regular user cannot update order status
     */
    public function test_regular_user_cannot_update_order_status()
    {
        $user = User::factory()->create(['role' => 'user']);
        $token = $user->createToken('test-token')->plainTextToken;

        $order = Order::factory()->create(['status' => 'pending']);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/admin/orders/' . $order->id . '/status', [
                'status' => 'processing'
            ]);

        $response->assertStatus(403);
    }

    /**
     * Test order validation - empty cart
     */
    public function test_cannot_create_order_with_empty_cart()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/orders', [
                'shipping_address' => $this->faker->address,
                'payment_method' => 'stripe'
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Cart is empty'
            ]);
    }

    /**
     * Test order stock reduction
     */
    public function test_order_reduces_product_stock()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $product = Product::factory()->create([
            'stock' => 10,
            'price' => 50.00
        ]);

        Cart::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 3
        ]);

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/orders', [
                'shipping_address' => $this->faker->address,
                'payment_method' => 'stripe'
            ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock' => 7 // 10 - 3
        ]);
    }

    /**
     * Test admin can view all orders
     */
    public function test_admin_can_view_all_orders()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $token = $admin->createToken('test-token')->plainTextToken;

        Order::factory()->count(5)->create();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/orders');

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data');
    }

    /**
     * Test order cancellation
     */
    public function test_user_can_cancel_pending_order()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'pending'
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/orders/' . $order->id . '/cancel');

        $response->assertStatus(200);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'cancelled'
        ]);
    }

    /**
     * Test cannot cancel shipped order
     */
    public function test_user_cannot_cancel_shipped_order()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'shipped'
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/orders/' . $order->id . '/cancel');

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Cannot cancel shipped or delivered orders'
            ]);
    }
}

