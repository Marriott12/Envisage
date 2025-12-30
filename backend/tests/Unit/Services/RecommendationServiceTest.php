<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use App\Services\AdvancedRecommendationService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RecommendationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $service;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(AdvancedRecommendationService::class);
        $this->user = User::factory()->create();
    }

    /** @test */
    public function it_generates_neural_network_recommendations()
    {
        Product::factory()->count(10)->create();

        $recommendations = $this->service->generateRecommendations(
            $this->user->id,
            'neural',
            5
        );

        $this->assertIsArray($recommendations);
        $this->assertLessThanOrEqual(5, count($recommendations));
    }

    /** @test */
    public function it_generates_collaborative_filtering_recommendations()
    {
        Product::factory()->count(10)->create();

        $recommendations = $this->service->generateRecommendations(
            $this->user->id,
            'collaborative',
            5
        );

        $this->assertIsArray($recommendations);
    }

    /** @test */
    public function it_respects_count_limit()
    {
        Product::factory()->count(20)->create();

        $recommendations = $this->service->generateRecommendations(
            $this->user->id,
            'neural',
            5
        );

        $this->assertLessThanOrEqual(5, count($recommendations));
    }

    /** @test */
    public function it_returns_empty_array_when_no_products()
    {
        $recommendations = $this->service->generateRecommendations(
            $this->user->id,
            'neural',
            5
        );

        $this->assertIsArray($recommendations);
        $this->assertEmpty($recommendations);
    }
}
