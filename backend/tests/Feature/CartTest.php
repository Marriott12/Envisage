<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Product;
use App\Models\Cart;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class CartTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /**
     * Test adding product to cart
     */
    public function test_user_can_add_product_to_cart()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;
        $product = Product::factory()->create(['stock' => 10]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/cart/add', [
                'product_id' => $product->id,
                'quantity' => 2
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Product added to cart successfully'
            ]);

        $this->assertDatabaseHas('carts', [
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 2
        ]);
    }

    /**
     * Test updating cart quantity
     */
    public function test_user_can_update_cart_quantity()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;
        $product = Product::factory()->create(['stock' => 10]);
        
        Cart::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 2
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/cart/update', [
                'product_id' => $product->id,
                'quantity' => 5
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('carts', [
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 5
        ]);
    }

    /**
     * Test removing product from cart
     */
    public function test_user_can_remove_product_from_cart()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;
        $product = Product::factory()->create();
        
        $cartItem = Cart::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 2
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson('/api/cart/remove/' . $product->id);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('carts', [
            'id' => $cartItem->id
        ]);
    }

    /**
     * Test viewing cart
     */
    public function test_user_can_view_cart()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;
        
        $products = Product::factory()->count(3)->create(['price' => 50.00]);
        
        foreach ($products as $product) {
            Cart::create([
                'user_id' => $user->id,
                'product_id' => $product->id,
                'quantity' => 2
            ]);
        }

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/cart');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'items' => [
                    '*' => ['product_id', 'quantity', 'product']
                ],
                'total'
            ])
            ->assertJsonPath('total', 300.00); // 3 products * 2 qty * $50
    }

    /**
     * Test clearing cart
     */
    public function test_user_can_clear_cart()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;
        
        Cart::factory()->count(3)->create(['user_id' => $user->id]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson('/api/cart/clear');

        $response->assertStatus(200);

        $this->assertDatabaseCount('carts', 0);
    }

    /**
     * Test cart validation - out of stock
     */
    public function test_cannot_add_out_of_stock_product()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;
        $product = Product::factory()->create(['stock' => 0]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/cart/add', [
                'product_id' => $product->id,
                'quantity' => 1
            ]);

        $response->assertStatus(422);
    }

    /**
     * Test cart validation - quantity exceeds stock
     */
    public function test_cannot_add_quantity_exceeding_stock()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;
        $product = Product::factory()->create(['stock' => 5]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/cart/add', [
                'product_id' => $product->id,
                'quantity' => 10
            ]);

        $response->assertStatus(422);
    }

    /**
     * Test cart requires authentication
     */
    public function test_cart_requires_authentication()
    {
        $response = $this->getJson('/api/cart');

        $response->assertStatus(401);
    }
}

