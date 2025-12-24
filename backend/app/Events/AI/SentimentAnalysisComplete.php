<?php

namespace App\Events\AI;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SentimentAnalysisComplete implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $productId;
    public $sellerId;
    public $totalReviews;
    public $overallSentiment;
    public $sentimentBreakdown;
    public $fakeReviewsDetected;
    public $timestamp;

    /**
     * Create a new event instance.
     *
     * @param int $productId
     * @param int $sellerId
     * @param int $totalReviews
     * @param string $overallSentiment
     * @param array $sentimentBreakdown
     * @param int $fakeReviewsDetected
     * @return void
     */
    public function __construct($productId, $sellerId, $totalReviews, $overallSentiment, $sentimentBreakdown, $fakeReviewsDetected = 0)
    {
        $this->productId = $productId;
        $this->sellerId = $sellerId;
        $this->totalReviews = $totalReviews;
        $this->overallSentiment = $overallSentiment;
        $this->sentimentBreakdown = $sentimentBreakdown;
        $this->fakeReviewsDetected = $fakeReviewsDetected;
        $this->timestamp = now()->toISOString();
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('ai.sentiment.seller.' . $this->sellerId);
    }

    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith()
    {
        return [
            'type' => 'sentiment_analysis_complete',
            'product_id' => $this->productId,
            'total_reviews' => $this->totalReviews,
            'overall_sentiment' => $this->overallSentiment,
            'sentiment_breakdown' => $this->sentimentBreakdown,
            'fake_reviews_detected' => $this->fakeReviewsDetected,
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
        return 'sentiment.analysis.complete';
    }
}
