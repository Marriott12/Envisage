<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\AdvancedFraudDetectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class FraudDetectionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(AdvancedFraudDetectionService::class);
    }

    /** @test */
    public function it_calculates_risk_score()
    {
        $transactionData = [
            'amount' => 500.00,
            'user_id' => 1,
            'ip_address' => '192.168.1.1',
            'device_id' => 'device_123'
        ];

        $result = $this->service->analyzeTransaction($transactionData);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('risk_score', $result);
        $this->assertArrayHasKey('risk_level', $result);
        $this->assertArrayHasKey('indicators', $result);
    }

    /** @test */
    public function it_detects_high_risk_transactions()
    {
        $transactionData = [
            'amount' => 10000.00, // Very high amount
            'user_id' => 1,
            'ip_address' => '1.2.3.4', // Unknown IP
            'device_id' => 'new_device_123'
        ];

        $result = $this->service->analyzeTransaction($transactionData);

        $this->assertGreaterThan(50, $result['risk_score']);
    }

    /** @test */
    public function it_classifies_risk_levels()
    {
        $transactionData = [
            'amount' => 50.00,
            'user_id' => 1,
            'ip_address' => '192.168.1.1',
            'device_id' => 'device_123'
        ];

        $result = $this->service->analyzeTransaction($transactionData);

        $this->assertContains($result['risk_level'], ['low', 'medium', 'high', 'critical']);
    }
}
