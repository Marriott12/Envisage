<?php

namespace App\Events\AI;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RecommendationGenerated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $userId;
    public $recommendations;
    public $algorithm;
    public $processingTime;
    public $timestamp;

    /**
     * Create a new event instance.
     *
     * @param int $userId
     * @param array $recommendations
     * @param string $algorithm
     * @param float $processingTime
     * @return void
     */
    public function __construct($userId, $recommendations, $algorithm, $processingTime)
    {
        $this->userId = $userId;
        $this->recommendations = $recommendations;
        $this->algorithm = $algorithm;
        $this->processingTime = $processingTime;
        $this->timestamp = now()->toISOString();
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('ai.user.' . $this->userId);
    }

    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith()
    {
        return [
            'type' => 'recommendation_generated',
            'recommendations' => $this->recommendations,
            'algorithm' => $this->algorithm,
            'processing_time' => $this->processingTime,
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
        return 'recommendation.generated';
    }
}
