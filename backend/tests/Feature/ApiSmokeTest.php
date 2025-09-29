<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiSmokeTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function registration_and_login_flow_works()
    {
    $response = $this->postJson('/api/register', [
            'name' => 'Test User',
            'email' => 'testuser@example.com',
            'password' => 'password123',
        ]);
        $response->assertStatus(201)->assertJsonStructure(['access_token', 'user']);

    $login = $this->postJson('/api/login', [
            'email' => 'testuser@example.com',
            'password' => 'password123',
        ]);
        $login->assertStatus(200)->assertJsonStructure(['access_token', 'user']);
    }

    /** @test */
    public function products_endpoints_work()
    {
    $response = $this->withHeaders(['Accept' => 'application/json'])->getJson('/api/products');
        $response->assertStatus(200);
    }

    /** @test */
    public function blog_posts_endpoints_work()
    {
    $response = $this->withHeaders(['Accept' => 'application/json'])->getJson('/api/blog-posts');
        $response->assertStatus(200);
    }

    /** @test */
    public function cart_endpoints_work()
    {
        // Ensure user and cart exist for user_id 1
        $user = \App\Models\User::firstOrCreate([
            'id' => 1,
        ], [
            'name' => 'Test User',
            'email' => 'testuser@example.com',
            'password' => bcrypt('password123'),
        ]);
        \App\Models\Cart::firstOrCreate(['user_id' => $user->id]);
        $response = $this->withHeaders(['Accept' => 'application/json'])->getJson('/api/cart/1');
        $response->assertStatus(200);
    }

    /** @test */
    public function payments_endpoints_work()
    {
    $response = $this->withHeaders(['Accept' => 'application/json'])->getJson('/api/payments');
        $response->assertStatus(200);
    }

    /** @test */
    public function orders_endpoints_work()
    {
    $response = $this->withHeaders(['Accept' => 'application/json'])->getJson('/api/orders');
        $response->assertStatus(200);
    }
}
