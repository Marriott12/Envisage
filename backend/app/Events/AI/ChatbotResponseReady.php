<?php

namespace App\Events\AI;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChatbotResponseReady implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $conversationId;
    public $userId;
    public $message;
    public $responseTime;
    public $suggestedActions;
    public $timestamp;

    /**
     * Create a new event instance.
     *
     * @param string $conversationId
     * @param int $userId
     * @param string $message
     * @param float $responseTime
     * @param array $suggestedActions
     * @return void
     */
    public function __construct($conversationId, $userId, $message, $responseTime, $suggestedActions = [])
    {
        $this->conversationId = $conversationId;
        $this->userId = $userId;
        $this->message = $message;
        $this->responseTime = $responseTime;
        $this->suggestedActions = $suggestedActions;
        $this->timestamp = now()->toISOString();
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('ai.chat.' . $this->conversationId);
    }

    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith()
    {
        return [
            'type' => 'chatbot_response',
            'message' => $this->message,
            'response_time' => $this->responseTime,
            'suggested_actions' => $this->suggestedActions,
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
        return 'chatbot.response.ready';
    }
}
