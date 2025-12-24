<?php

namespace App\Events\AI;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ABTestWinnerDetermined implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $experimentName;
    public $winningVariant;
    public $isStatisticallySignificant;
    public $liftPercentage;
    public $confidenceLevel;
    public $metrics;
    public $timestamp;

    /**
     * Create a new event instance.
     *
     * @param string $experimentName
     * @param string $winningVariant
     * @param bool $isStatisticallySignificant
     * @param float $liftPercentage
     * @param float $confidenceLevel
     * @param array $metrics
     * @return void
     */
    public function __construct($experimentName, $winningVariant, $isStatisticallySignificant, $liftPercentage, $confidenceLevel, $metrics)
    {
        $this->experimentName = $experimentName;
        $this->winningVariant = $winningVariant;
        $this->isStatisticallySignificant = $isStatisticallySignificant;
        $this->liftPercentage = $liftPercentage;
        $this->confidenceLevel = $confidenceLevel;
        $this->metrics = $metrics;
        $this->timestamp = now()->toISOString();
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        // Broadcast to admin channel for A/B test results
        return new PrivateChannel('ai.abtest.admin');
    }

    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith()
    {
        return [
            'type' => 'abtest_winner_determined',
            'experiment_name' => $this->experimentName,
            'winning_variant' => $this->winningVariant,
            'is_statistically_significant' => $this->isStatisticallySignificant,
            'lift_percentage' => $this->liftPercentage,
            'confidence_level' => $this->confidenceLevel,
            'metrics' => $this->metrics,
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
        return 'abtest.winner.determined';
    }
}
