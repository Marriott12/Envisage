<?php

namespace Tests\Feature\AI;

use Tests\TestCase;
use App\Models\User;
use App\Models\Order;
use App\Services\AdvancedFraudDetectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class FraudDetectionTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $seller;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->seller = User::factory()->create(['role' => 'seller']);
    }

    /** @test */
    public function it_can_analyze_transaction_for_fraud()
    {
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'total' => 500.00
        ]);

        $response = $this->actingAs($this->seller, 'sanctum')
            ->postJson('/api/ai/fraud/analyze', [
                'order_id' => $order->id
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'risk_score',
                    'risk_level',
                    'indicators',
                    'recommendation'
                ]
            ]);
    }

    /** @test */
    public function it_can_get_fraud_alerts_for_seller()
    {
        $response = $this->actingAs($this->seller, 'sanctum')
            ->getJson('/api/ai/fraud/alerts');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'alert_id',
                        'transaction_id',
                        'risk_score',
                        'risk_level'
                    ]
                ]
            ]);
    }

    /** @test */
    public function it_can_review_fraud_alert()
    {
        $response = $this->actingAs($this->seller, 'sanctum')
            ->postJson('/api/ai/fraud/alerts/review', [
                'alert_id' => 'test_alert_123',
                'decision' => 'approve',
                'notes' => 'Customer verified'
            ]);

        $response->assertStatus(200);
    }

    /** @test */
    public function it_requires_seller_role_for_fraud_analysis()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/ai/fraud/analyze', [
                'order_id' => 1
            ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function it_validates_fraud_review_decision()
    {
        $response = $this->actingAs($this->seller, 'sanctum')
            ->postJson('/api/ai/fraud/alerts/review', [
                'alert_id' => 'test_alert_123',
                'decision' => 'invalid_decision'
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['decision']);
    }
}
