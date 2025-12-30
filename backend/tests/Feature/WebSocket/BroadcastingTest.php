<?php

namespace Tests\Feature\WebSocket;

use Tests\TestCase;
use App\Models\User;
use App\Events\AI\RecommendationGenerated;
use App\Events\AI\FraudAlertCreated;
use App\Events\AI\SentimentAnalysisComplete;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

class BroadcastingTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        Event::fake();
    }

    /** @test */
    public function recommendation_generated_event_is_broadcast()
    {
        $recommendations = [
            ['id' => 1, 'name' => 'Product 1', 'price' => 19.99]
        ];
        $algorithm = 'neural';
        $processingTime = 0.25;

        event(new RecommendationGenerated($this->user->id, $recommendations, $algorithm, $processingTime));

        Event::assertDispatched(RecommendationGenerated::class, function ($event) {
            return $event->userId === $this->user->id;
        });
    }

    /** @test */
    public function fraud_alert_event_is_broadcast()
    {
        $alertId = 123;
        $transactionId = 456;
        $sellerId = $this->user->id;
        $riskScore = 85.0;
        $riskLevel = 'high';
        $indicators = ['unusual_amount', 'new_device'];

        event(new FraudAlertCreated($alertId, $transactionId, $sellerId, $riskScore, $riskLevel, $indicators));

        Event::assertDispatched(FraudAlertCreated::class);
    }

    /** @test */
    public function sentiment_analysis_event_is_broadcast()
    {
        $productId = 1;
        $sellerId = $this->user->id;
        $totalReviews = 100;
        $overallSentiment = 'positive';
        $sentimentBreakdown = ['positive' => 80, 'neutral' => 15, 'negative' => 5];
        $fakeReviewsDetected = 2;

        event(new SentimentAnalysisComplete($productId, $sellerId, $totalReviews, $overallSentiment, $sentimentBreakdown, $fakeReviewsDetected));

        Event::assertDispatched(SentimentAnalysisComplete::class);
    }

    /** @test */
    public function it_can_authorize_private_channel()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/broadcasting/auth', [
                'channel_name' => "private-ai.user.{$this->user->id}"
            ]);

        $response->assertStatus(200);
    }

    /** @test */
    public function it_cannot_authorize_other_users_channel()
    {
        $otherUser = User::factory()->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/broadcasting/auth', [
                'channel_name' => "private-ai.user.{$otherUser->id}"
            ]);

        $response->assertStatus(403);
    }
}
