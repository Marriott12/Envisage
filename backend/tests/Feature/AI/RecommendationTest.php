<?php

namespace Tests\Feature\AI;

use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use App\Services\AdvancedRecommendationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

class RecommendationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;
    protected $product;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->product = Product::factory()->create([
            'name' => 'Test Product',
            'price' => 99.99,
            'stock' => 100
        ]);
    }

    /** @test */
    public function it_can_generate_recommendations_for_authenticated_user()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/ai/recommendations/generate', [
                'algorithm' => 'neural',
                'count' => 5
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'algorithm',
                    'processing_time',
                    'recommendations' => [
                        '*' => ['id', 'name', 'price']
                    ]
                ]
            ]);
    }

    /** @test */
    public function it_validates_recommendation_algorithm()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/ai/recommendations/generate', [
                'algorithm' => 'invalid_algorithm',
                'count' => 5
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['algorithm']);
    }

    /** @test */
    public function it_can_get_personalized_recommendations()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/recommendations/history-based');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'price']
                ]
            ]);
    }

    /** @test */
    public function it_can_get_similar_products()
    {
        $response = $this->getJson("/api/products/{$this->product->id}/similar");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'price']
                ]
            ]);
    }

    /** @test */
    public function it_tracks_product_views()
    {
        $response = $this->postJson("/api/products/{$this->product->id}/track-view");

        $response->assertStatus(200);
    }

    /** @test */
    public function it_requires_authentication_for_ai_recommendations()
    {
        $response = $this->postJson('/api/ai/recommendations/generate', [
            'algorithm' => 'neural',
            'count' => 5
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function it_respects_count_limits()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/ai/recommendations/generate', [
                'algorithm' => 'neural',
                'count' => 1000
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['count']);
    }
}
