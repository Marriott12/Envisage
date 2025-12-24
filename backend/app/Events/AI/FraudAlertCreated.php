<?php

namespace App\Events\AI;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FraudAlertCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $alertId;
    public $transactionId;
    public $sellerId;
    public $riskScore;
    public $riskLevel;
    public $indicators;
    public $timestamp;

    /**
     * Create a new event instance.
     *
     * @param int $alertId
     * @param int $transactionId
     * @param int $sellerId
     * @param float $riskScore
     * @param string $riskLevel
     * @param array $indicators
     * @return void
     */
    public function __construct($alertId, $transactionId, $sellerId, $riskScore, $riskLevel, $indicators)
    {
        $this->alertId = $alertId;
        $this->transactionId = $transactionId;
        $this->sellerId = $sellerId;
        $this->riskScore = $riskScore;
        $this->riskLevel = $riskLevel;
        $this->indicators = $indicators;
        $this->timestamp = now()->toISOString();
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return [
            new PrivateChannel('ai.fraud.seller.' . $this->sellerId),
            new PrivateChannel('ai.fraud.admin'), // Admin notification channel
        ];
    }

    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith()
    {
        return [
            'type' => 'fraud_alert',
            'alert_id' => $this->alertId,
            'transaction_id' => $this->transactionId,
            'risk_score' => $this->riskScore,
            'risk_level' => $this->riskLevel,
            'indicators' => $this->indicators,
            'timestamp' => $this->timestamp,
        ];
    }

    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs()
    {
        return 'fraud.alert.created';
    }
}
