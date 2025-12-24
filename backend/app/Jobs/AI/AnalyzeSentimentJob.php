<?php

namespace App\Jobs\AI;

use App\Models\Product;
use App\Models\Review;
use App\Models\SentimentCache;
use App\Services\AIMetricsService;
use App\Events\AI\SentimentAnalysisComplete;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class AnalyzeSentimentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $productId;

    public $tries = 3;
    public $retryAfter = 60;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($productId)
    {
        $this->productId = $productId;
    }

    /**
     * Execute the job - Analyze sentiment for product reviews
     *
     * @return void
     */
    public function handle(AIMetricsService $metricsService)
    {
        $startTime = microtime(true);

        try {
            $product = Product::findOrFail($this->productId);
            $reviews = Review::where('product_id', $this->productId)->get();

            if ($reviews->isEmpty()) {
                Log::info('No reviews to analyze', ['product_id' => $this->productId]);
                return;
            }

            // Analyze sentiment for each review
            $sentiments = [];
            $fakeCount = 0;
            $themes = [];

            foreach ($reviews as $review) {
                $sentiment = $this->analyzeSentiment($review->comment);
                $sentiments[] = $sentiment;

                // Check for fake reviews
                if ($this->isFakeReview($review)) {
                    $fakeCount++;
                    $review->update(['is_fake' => true]);
                }

                // Extract themes
                $reviewThemes = $this->extractThemes($review->comment);
                $themes = array_merge($themes, $reviewThemes);
            }

            // Calculate overall sentiment
            $overallSentiment = array_sum($sentiments) / count($sentiments);
            
            // Calculate distribution
            $distribution = [
                'positive' => count(array_filter($sentiments, fn($s) => $s > 0.3)),
                'neutral' => count(array_filter($sentiments, fn($s) => $s >= -0.3 && $s <= 0.3)),
                'negative' => count(array_filter($sentiments, fn($s) => $s < -0.3)),
            ];

            // Generate AI summary
            $summary = $this->generateSummary($reviews, $overallSentiment);

            // Count theme occurrences
            $keyThemes = array_count_values($themes);
            arsort($keyThemes);
            $keyThemes = array_slice($keyThemes, 0, 10, true);

            // Cache sentiment analysis
            SentimentCache::updateOrCreate(
                ['product_id' => $this->productId],
                [
                    'overall_sentiment' => round($overallSentiment, 2),
                    'sentiment_distribution' => $distribution,
                    'ai_summary' => $summary,
                    'key_themes' => array_keys($keyThemes),
                    'review_count' => $reviews->count(),
                    'fake_review_count' => $fakeCount,
                    'last_analyzed_at' => now(),
                ]
            );

            // Track metrics
            $responseTime = (microtime(true) - $startTime) * 1000;
            $metricsService->trackRequest(
                'sentiment_analysis',
                'analyze_product',
                $responseTime,
                true,
                [
                    'product_id' => $this->productId,
                    'review_count' => $reviews->count(),
                    'fake_count' => $fakeCount,
                    'overall_sentiment' => $overallSentiment,
                ]
            );

            Log::info('Sentiment analysis completed', [
                'product_id' => $this->productId,
                'overall_sentiment' => $overallSentiment,
                'time_ms' => $responseTime,
            ]);

            // Broadcast real-time event
            $sentimentLabel = $overallSentiment > 0.3 ? 'positive' : ($overallSentiment < -0.3 ? 'negative' : 'neutral');
            event(new SentimentAnalysisComplete(
                $this->productId,
                $product->seller_id ?? null,
                $reviews->count(),
                $sentimentLabel,
                $distribution,
                $fakeCount
            ));

        } catch (\Exception $e) {
            $responseTime = (microtime(true) - $startTime) * 1000;
            $metricsService->trackRequest(
                'sentiment_analysis',
                'analyze_product',
                $responseTime,
                false,
                ['error' => $e->getMessage()]
            );

            Log::error('Sentiment analysis failed', [
                'product_id' => $this->productId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Analyze sentiment of text (-1 to 1)
     */
    protected function analyzeSentiment($text)
    {
        // Simplified sentiment analysis
        // In production, use BERT, VADER, or external API
        $positiveWords = ['good', 'great', 'excellent', 'amazing', 'love', 'best', 'perfect', 'awesome'];
        $negativeWords = ['bad', 'poor', 'terrible', 'worst', 'hate', 'awful', 'disappointing'];

        $text = strtolower($text);
        $positive = 0;
        $negative = 0;

        foreach ($positiveWords as $word) {
            $positive += substr_count($text, $word);
        }

        foreach ($negativeWords as $word) {
            $negative += substr_count($text, $word);
        }

        $total = $positive + $negative;
        if ($total === 0) return 0;

        return ($positive - $negative) / $total;
    }

    /**
     * Detect fake reviews
     */
    protected function isFakeReview($review)
    {
        // Simplified fake detection
        // In production, use ML model trained on labeled data
        $text = strtolower($review->comment);
        
        // Check for spam patterns
        $spamPatterns = ['click here', 'visit', 'http', 'www.', 'buy now'];
        foreach ($spamPatterns as $pattern) {
            if (strpos($text, $pattern) !== false) {
                return true;
            }
        }

        // Check review length (very short or very long)
        if (strlen($review->comment) < 20 || strlen($review->comment) > 2000) {
            return true;
        }

        return false;
    }

    /**
     * Extract key themes from reviews
     */
    protected function extractThemes($text)
    {
        $themes = [];
        $text = strtolower($text);

        $themeKeywords = [
            'quality' => ['quality', 'build', 'material', 'durable'],
            'price' => ['price', 'cost', 'expensive', 'cheap', 'value'],
            'shipping' => ['shipping', 'delivery', 'arrived', 'package'],
            'customer_service' => ['service', 'support', 'help', 'response'],
            'design' => ['design', 'look', 'appearance', 'style'],
        ];

        foreach ($themeKeywords as $theme => $keywords) {
            foreach ($keywords as $keyword) {
                if (strpos($text, $keyword) !== false) {
                    $themes[] = $theme;
                    break;
                }
            }
        }

        return $themes;
    }

    /**
     * Generate AI summary
     */
    protected function generateSummary($reviews, $overallSentiment)
    {
        $count = $reviews->count();
        $sentiment = $overallSentiment > 0.3 ? 'positive' : ($overallSentiment < -0.3 ? 'negative' : 'mixed');

        return "Based on {$count} reviews, customers have a {$sentiment} sentiment about this product.";
    }
}
