<?php

namespace Tests\Feature\AI;

use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use App\Services\SentimentAnalysisService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SentimentAnalysisTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $seller;
    protected $product;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->seller = User::factory()->create(['role' => 'seller']);
        $this->product = Product::factory()->create([
            'seller_id' => $this->seller->id
        ]);
    }

    /** @test */
    public function it_can_analyze_product_sentiment()
    {
        $response = $this->actingAs($this->seller, 'sanctum')
            ->postJson('/api/ai/sentiment/analyze', [
                'product_id' => $this->product->id
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'product_id',
                    'total_reviews',
                    'overall_sentiment',
                    'sentiment_breakdown',
                    'fake_reviews_detected'
                ]
            ]);
    }

    /** @test */
    public function it_can_get_sentiment_trends()
    {
        $response = $this->actingAs($this->seller, 'sanctum')
            ->getJson("/api/ai/sentiment/trends/{$this->product->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'trends' => [
                        '*' => ['date', 'sentiment', 'count']
                    ]
                ]
            ]);
    }

    /** @test */
    public function seller_can_only_access_own_products_sentiment()
    {
        $otherSeller = User::factory()->create(['role' => 'seller']);
        $otherProduct = Product::factory()->create([
            'seller_id' => $otherSeller->id
        ]);

        $response = $this->actingAs($this->seller, 'sanctum')
            ->postJson('/api/ai/sentiment/analyze', [
                'product_id' => $otherProduct->id
            ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function it_requires_authentication_for_sentiment_analysis()
    {
        $response = $this->postJson('/api/ai/sentiment/analyze', [
            'product_id' => $this->product->id
        ]);

        $response->assertStatus(401);
    }
}
