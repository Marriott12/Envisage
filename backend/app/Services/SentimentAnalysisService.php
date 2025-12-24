<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Order;
use App\Models\User;
use App\Models\Review;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Advanced Sentiment Analysis & Review Intelligence Service
 * 
 * Features:
 * - Deep learning sentiment analysis (BERT, RoBERTa)
 * - Aspect-based opinion mining
 * - Fake review detection
 * - Emotion detection
 * - Review summarization
 * - Automated response suggestions
 */
class SentimentAnalysisService
{
    protected $mlServiceUrl;

    public function __construct()
    {
        $this->mlServiceUrl = config('services.ml.url', env('ML_SERVICE_URL', 'http://localhost:5000'));
    }

    /**
     * Deep learning sentiment analysis using BERT
     */
    public function analyzeSentiment($text)
    {
        try {
            $response = Http::timeout(5)->post("{$this->mlServiceUrl}/api/sentiment/analyze", [
                'text' => $text,
                'model' => 'bert_sentiment',
            ]);

            if ($response->successful()) {
                return $response->json();
            }
        } catch (\Exception $e) {
            \Log::warning("BERT sentiment analysis failed: " . $e->getMessage());
        }

        // Fallback to basic sentiment
        return $this->basicSentiment($text);
    }

    /**
     * Aspect-based sentiment analysis
     * Analyzes sentiment for specific product aspects (quality, price, delivery, etc.)
     */
    public function aspectBasedSentiment($reviewText, $productId = null)
    {
        try {
            $response = Http::timeout(10)->post("{$this->mlServiceUrl}/api/sentiment/aspect-based", [
                'text' => $reviewText,
                'model' => 'aspect_bert',
            ]);

            if ($response->successful()) {
                return $response->json()['aspects'];
            }
        } catch (\Exception $e) {
            \Log->warning("Aspect-based sentiment failed: " . $e->getMessage());
        }

        // Fallback to keyword-based aspect extraction
        return $this->keywordAspectExtraction($reviewText);
    }

    /**
     * Keyword-based aspect extraction (fallback)
     */
    protected function keywordAspectExtraction($text)
    {
        $aspects = [
            'quality' => [
                'keywords' => ['quality', 'durable', 'well-made', 'sturdy', 'fragile', 'cheap'],
                'sentiment' => 'neutral',
                'score' => 0,
            ],
            'price' => [
                'keywords' => ['price', 'expensive', 'cheap', 'value', 'worth', 'overpriced'],
                'sentiment' => 'neutral',
                'score' => 0,
            ],
            'delivery' => [
                'keywords' => ['delivery', 'shipping', 'arrived', 'fast', 'slow', 'damaged'],
                'sentiment' => 'neutral',
                'score' => 0,
            ],
            'design' => [
                'keywords' => ['design', 'look', 'appearance', 'style', 'beautiful', 'ugly'],
                'sentiment' => 'neutral',
                'score' => 0,
            ],
            'usability' => [
                'keywords' => ['easy', 'difficult', 'user-friendly', 'complicated', 'intuitive'],
                'sentiment' => 'neutral',
                'score' => 0,
            ],
        ];

        $text = strtolower($text);

        foreach ($aspects as $aspect => &$data) {
            $found = false;
            foreach ($data['keywords'] as $keyword) {
                if (strpos($text, $keyword) !== false) {
                    $found = true;
                    // Extract sentiment for this aspect
                    $contextStart = max(0, strpos($text, $keyword) - 50);
                    $contextEnd = min(strlen($text), strpos($text, $keyword) + 50);
                    $context = substr($text, $contextStart, $contextEnd - $contextStart);
                    
                    $aspectSentiment = $this->basicSentiment($context);
                    $data['sentiment'] = $aspectSentiment['sentiment'];
                    $data['score'] = $aspectSentiment['score'];
                    break;
                }
            }
            
            if (!$found) {
                unset($aspects[$aspect]);
            }
        }

        return $aspects;
    }

    /**
     * Detect fake reviews using ML
     */
    public function detectFakeReview($reviewData)
    {
        try {
            $response = Http::timeout(5)->post("{$this->mlServiceUrl}/api/sentiment/fake-detection", [
                'text' => $reviewData['text'],
                'rating' => $reviewData['rating'],
                'user_history' => $reviewData['user_history'] ?? [],
                'model' => 'fake_review_detector',
            ]);

            if ($response->successful()) {
                return $response->json();
            }
        } catch (\Exception $e) {
            \Log::warning("Fake review detection failed: " . $e->getMessage());
        }

        // Fallback to heuristic-based detection
        return $this->heuristicFakeDetection($reviewData);
    }

    /**
     * Heuristic-based fake review detection
     */
    protected function heuristicFakeDetection($reviewData)
    {
        $suspicionScore = 0.0;
        $reasons = [];

        $text = $reviewData['text'];
        $rating = $reviewData['rating'];

        // Check 1: Generic text
        if ($this->isGenericText($text)) {
            $suspicionScore += 0.3;
            $reasons[] = 'Generic review text';
        }

        // Check 2: Excessive positivity/negativity
        $sentiment = $this->basicSentiment($text);
        if (abs($sentiment['score']) > 0.9 && strlen($text) < 100) {
            $suspicionScore += 0.2;
            $reasons[] = 'Extremely emotional but short';
        }

        // Check 3: Rating-sentiment mismatch
        if ($this->hasRatingSentimentMismatch($rating, $sentiment)) {
            $suspicionScore += 0.4;
            $reasons[] = 'Rating does not match sentiment';
        }

        // Check 4: Too short
        if (strlen($text) < 20) {
            $suspicionScore += 0.1;
            $reasons[] = 'Very short review';
        }

        // Check 5: All caps or excessive punctuation
        if ($this->hasExcessiveFormatting($text)) {
            $suspicionScore += 0.15;
            $reasons[] = 'Excessive formatting';
        }

        // Check 6: User behavior patterns
        if (isset($reviewData['user_history'])) {
            if ($this->hasSuspiciousUserPattern($reviewData['user_history'])) {
                $suspicionScore += 0.3;
                $reasons[] = 'Suspicious user review pattern';
            }
        }

        $isFake = $suspicionScore >= 0.6;

        return [
            'is_fake' => $isFake,
            'confidence' => min($suspicionScore, 1.0),
            'suspicion_score' => $suspicionScore,
            'reasons' => $reasons,
        ];
    }

    /**
     * Detect emotions in text (beyond positive/negative)
     */
    public function detectEmotions($text)
    {
        try {
            $response = Http::timeout(5)->post("{$this->mlServiceUrl}/api/sentiment/emotions", [
                'text' => $text,
                'model' => 'emotion_classifier',
            ]);

            if ($response->successful()) {
                return $response->json()['emotions'];
            }
        } catch (\Exception $e) {
            \Log::warning("Emotion detection failed: " . $e->getMessage());
        }

        // Fallback to keyword-based emotion detection
        return $this->keywordEmotionDetection($text);
    }

    /**
     * Keyword-based emotion detection
     */
    protected function keywordEmotionDetection($text)
    {
        $emotionKeywords = [
            'joy' => ['happy', 'love', 'great', 'excellent', 'wonderful', 'amazing', 'delighted'],
            'anger' => ['angry', 'furious', 'terrible', 'hate', 'worst', 'outraged'],
            'sadness' => ['sad', 'disappointed', 'unhappy', 'regret', 'sorry'],
            'surprise' => ['surprised', 'unexpected', 'wow', 'shocking', 'amazing'],
            'fear' => ['worried', 'concerned', 'afraid', 'scary', 'nervous'],
            'trust' => ['reliable', 'trustworthy', 'confident', 'believe'],
        ];

        $text = strtolower($text);
        $emotions = [];

        foreach ($emotionKeywords as $emotion => $keywords) {
            $score = 0;
            foreach ($keywords as $keyword) {
                if (strpos($text, $keyword) !== false) {
                    $score++;
                }
            }
            if ($score > 0) {
                $emotions[$emotion] = $score / count($keywords);
            }
        }

        arsort($emotions);
        return $emotions;
    }

    /**
     * Generate review summary
     */
    public function summarizeReviews($productId, $limit = 100)
    {
        $reviews = Review::where('product_id', $productId)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->pluck('comment')
            ->toArray();

        if (empty($reviews)) {
            return null;
        }

        try {
            $response = Http::timeout(15)->post("{$this->mlServiceUrl}/api/sentiment/summarize", [
                'reviews' => $reviews,
                'model' => 'bart_summarizer',
                'max_length' => 150,
            ]);

            if ($response->successful()) {
                return $response->json()['summary'];
            }
        } catch (\Exception $e) {
            \Log::warning("Review summarization failed: " . $e->getMessage());
        }

        // Fallback: extractive summary
        return $this->extractiveSummary($reviews);
    }

    /**
     * Extractive summarization (fallback)
     */
    protected function extractiveSummary($reviews)
    {
        // Find most common themes
        $allText = implode(' ', $reviews);
        $words = str_word_count(strtolower($allText), 1);
        
        $stopWords = ['the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with'];
        $words = array_diff($words, $stopWords);
        
        $wordCounts = array_count_values($words);
        arsort($wordCounts);
        
        $topWords = array_slice(array_keys($wordCounts), 0, 10);
        
        return 'Common themes: ' . implode(', ', $topWords);
    }

    /**
     * Generate automated response suggestion
     */
    public function suggestResponse($review)
    {
        $sentiment = $this->analyzeSentiment($review['text']);

        try {
            $response = Http::timeout(10)->post("{$this->mlServiceUrl}/api/sentiment/suggest-response", [
                'review' => $review['text'],
                'rating' => $review['rating'],
                'sentiment' => $sentiment['sentiment'],
            ]);

            if ($response->successful()) {
                return $response->json()['suggested_response'];
            }
        } catch (\Exception $e) {
            \Log::warning("Response suggestion failed: " . $e->getMessage());
        }

        // Fallback to template-based responses
        return $this->templateResponse($review, $sentiment);
    }

    /**
     * Template-based response suggestions
     */
    protected function templateResponse($review, $sentiment)
    {
        $templates = [
            'positive' => [
                "Thank you so much for your wonderful review! We're thrilled you love your purchase.",
                "We're delighted to hear you're happy with your purchase! Thank you for choosing us.",
                "Thank you for the glowing review! We appreciate your support.",
            ],
            'negative' => [
                "We're sorry to hear about your experience. Please contact our support team at support@example.com so we can make this right.",
                "Thank you for your feedback. We take your concerns seriously and would like to resolve this issue. Please reach out to us.",
                "We apologize for any inconvenience. Your satisfaction is our priority. Let us help fix this.",
            ],
            'neutral' => [
                "Thank you for your feedback! We appreciate your honest review.",
                "Thanks for sharing your thoughts. We're always working to improve.",
            ],
        ];

        $sentimentType = $sentiment['sentiment'];
        $options = $templates[$sentimentType] ?? $templates['neutral'];

        return $options[array_rand($options)];
    }

    /**
     * Analyze overall product sentiment from all reviews
     */
    public function analyzeProductSentiment($productId)
    {
        $cacheKey = "product_sentiment:{$productId}";

        return Cache::remember($cacheKey, 3600, function () use ($productId) {
            $reviews = Review::where('product_id', $productId)
                ->select('comment', 'rating')
                ->get();

            if ($reviews->isEmpty()) {
                return null;
            }

            $sentiments = [];
            $aspects = [];

            foreach ($reviews as $review) {
                $sentiment = $this->analyzeSentiment($review->comment);
                $sentiments[] = $sentiment['score'];

                $reviewAspects = $this->aspectBasedSentiment($review->comment, $productId);
                foreach ($reviewAspects as $aspect => $data) {
                    if (!isset($aspects[$aspect])) {
                        $aspects[$aspect] = [];
                    }
                    $aspects[$aspect][] = $data['score'];
                }
            }

            // Calculate averages
            $avgSentiment = array_sum($sentiments) / count($sentiments);
            
            $aspectAverages = [];
            foreach ($aspects as $aspect => $scores) {
                $aspectAverages[$aspect] = array_sum($scores) / count($scores);
            }

            return [
                'overall_sentiment' => $avgSentiment,
                'sentiment_label' => $this->scoreToLabel($avgSentiment),
                'aspect_sentiments' => $aspectAverages,
                'total_reviews' => $reviews->count(),
                'positive_count' => $reviews->where('rating', '>=', 4)->count(),
                'negative_count' => $reviews->where('rating', '<=', 2)->count(),
            ];
        });
    }

    /**
     * Batch analyze sentiment for recent reviews
     */
    public function batchAnalyzeRecentReviews($hours = 24)
    {
        $reviews = Review::where('created_at', '>=', now()->subHours($hours))
            ->whereNull('sentiment_score')
            ->get();

        $analyzed = 0;

        foreach ($reviews as $review) {
            try {
                $sentiment = $this->analyzeSentiment($review->comment);
                $fakeCheck = $this->detectFakeReview([
                    'text' => $review->comment,
                    'rating' => $review->rating,
                ]);

                $review->update([
                    'sentiment_score' => $sentiment['score'],
                    'sentiment_label' => $sentiment['sentiment'],
                    'is_fake' => $fakeCheck['is_fake'],
                    'fake_confidence' => $fakeCheck['confidence'],
                ]);

                $analyzed++;
            } catch (\Exception $e) {
                \Log::error("Failed to analyze review {$review->id}: " . $e->getMessage());
            }
        }

        return $analyzed;
    }

    // Helper methods
    protected function basicSentiment($text)
    {
        $nlpService = app(NLPService::class);
        return $nlpService->analyzeSentiment($text);
    }

    protected function isGenericText($text)
    {
        $genericPhrases = [
            'great product',
            'highly recommend',
            'good quality',
            'fast shipping',
            'as described',
        ];

        $text = strtolower($text);
        $matches = 0;

        foreach ($genericPhrases as $phrase) {
            if (strpos($text, $phrase) !== false) {
                $matches++;
            }
        }

        return $matches >= 2 && strlen($text) < 150;
    }

    protected function hasRatingSentimentMismatch($rating, $sentiment)
    {
        if ($rating >= 4 && $sentiment['sentiment'] === 'negative') {
            return true;
        }
        if ($rating <= 2 && $sentiment['sentiment'] === 'positive') {
            return true;
        }
        return false;
    }

    protected function hasExcessiveFormatting($text)
    {
        $upperRatio = strlen(preg_replace('/[^A-Z]/', '', $text)) / max(strlen($text), 1);
        $exclamationCount = substr_count($text, '!');
        
        return $upperRatio > 0.5 || $exclamationCount > 5;
    }

    protected function hasSuspiciousUserPattern($userHistory)
    {
        // Check for review spam patterns
        if (count($userHistory) > 10) {
            $sameDayReviews = 0;
            foreach ($userHistory as $review) {
                if (strtotime($review['created_at']) > strtotime('-1 day')) {
                    $sameDayReviews++;
                }
            }
            return $sameDayReviews > 5;
        }
        return false;
    }

    protected function scoreToLabel($score)
    {
        if ($score > 0.3) return 'positive';
        if ($score < -0.3) return 'negative';
        return 'neutral';
    }
}
