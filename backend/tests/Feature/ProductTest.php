<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /**
     * Test listing all products
     */
    public function test_can_list_products()
    {
        Product::factory()->count(5)->create();

        $response = $this->getJson('/api/products');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'description', 'price', 'stock']
                ]
            ]);
    }

    /**
     * Test creating a product (admin)
     */
    public function test_admin_can_create_product()
    {
        Storage::fake('public');

        $admin = User::factory()->create(['role' => 'admin']);
        $token = $admin->createToken('test-token')->plainTextToken;

        $productData = [
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->paragraph,
            'price' => $this->faker->randomFloat(2, 10, 1000),
            'stock' => $this->faker->numberBetween(1, 100),
            'category' => $this->faker->word,
            'image' => UploadedFile::fake()->image('product.jpg')
        ];

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/admin/products', $productData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => ['id', 'name', 'price', 'image_url']
            ]);

        $this->assertDatabaseHas('products', [
            'name' => $productData['name'],
            'price' => $productData['price']
        ]);
    }

    /**
     * Test regular user cannot create product
     */
    public function test_regular_user_cannot_create_product()
    {
        $user = User::factory()->create(['role' => 'user']);
        $token = $user->createToken('test-token')->plainTextToken;

        $productData = [
            'name' => 'Test Product',
            'price' => 99.99,
            'stock' => 10
        ];

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/admin/products', $productData);

        $response->assertStatus(403);
    }

    /**
     * Test updating a product
     */
    public function test_admin_can_update_product()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $token = $admin->createToken('test-token')->plainTextToken;
        $product = Product::factory()->create();

        $updateData = [
            'name' => 'Updated Product Name',
            'price' => 149.99
        ];

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/admin/products/' . $product->id, $updateData);

        $response->assertStatus(200);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Updated Product Name',
            'price' => 149.99
        ]);
    }

    /**
     * Test deleting a product
     */
    public function test_admin_can_delete_product()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $token = $admin->createToken('test-token')->plainTextToken;
        $product = Product::factory()->create();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson('/api/admin/products/' . $product->id);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('products', [
            'id' => $product->id
        ]);
    }

    /**
     * Test product search
     */
    public function test_can_search_products()
    {
        Product::factory()->create(['name' => 'Blue Widget']);
        Product::factory()->create(['name' => 'Red Widget']);
        Product::factory()->create(['name' => 'Green Gadget']);

        $response = $this->getJson('/api/products?search=Widget');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    /**
     * Test getting seller's own products
     */
    public function test_seller_can_view_own_products()
    {
        $seller = User::factory()->create(['role' => 'seller']);
        $token = $seller->createToken('test-token')->plainTextToken;

        Product::factory()->count(3)->create(['seller_id' => $seller->id]);
        Product::factory()->count(2)->create(); // Other seller's products

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/seller/products');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }
}

