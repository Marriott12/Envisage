<?php

namespace App\Http\Controllers;

use App\Services\SentimentAnalysisService;
use App\Models\Review;
use Illuminate\Http\Request;

class SentimentController extends Controller
{
    protected $sentimentService;

    public function __construct(SentimentAnalysisService $sentimentService)
    {
        $this->sentimentService = $sentimentService;
    }

    /**
     * Analyze review sentiment
     */
    public function analyze(Request $request)
    {
        $request->validate([
            'text' => 'required|string|max:5000',
        ]);

        $text = $request->input('text');

        $analysis = $this->sentimentService->analyzeSentiment($text);

        return response()->json([
            'success' => true,
            'data' => $analysis,
        ]);
    }

    /**
     * Aspect-based sentiment analysis
     */
    public function aspectBased(Request $request)
    {
        $request->validate([
            'review_text' => 'required|string|max:5000',
        ]);

        $reviewText = $request->input('review_text');

        $aspects = $this->sentimentService->aspectBasedSentiment($reviewText);

        return response()->json([
            'success' => true,
            'data' => $aspects,
        ]);
    }

    /**
     * Detect fake reviews
     */
    public function detectFake(Request $request)
    {
        $request->validate([
            'review_text' => 'required|string|max:5000',
            'rating' => 'required|integer|min:1|max:5',
            'user_id' => 'nullable|exists:users,id',
        ]);

        $reviewData = $request->only(['review_text', 'rating', 'user_id']);
        $reviewData['created_at'] = now();

        $result = $this->sentimentService->detectFakeReview($reviewData);

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    /**
     * Detect emotions in text
     */
    public function detectEmotions(Request $request)
    {
        $request->validate([
            'text' => 'required|string|max:5000',
        ]);

        $text = $request->input('text');

        $emotions = $this->sentimentService->detectEmotions($text);

        return response()->json([
            'success' => true,
            'data' => $emotions,
        ]);
    }

    /**
     * Summarize product reviews
     */
    public function summarize(Request $request, $productId)
    {
        $limit = $request->input('limit', 100);

        $summary = $this->sentimentService->summarizeReviews($productId, $limit);

        return response()->json([
            'success' => true,
            'data' => $summary,
        ]);
    }

    /**
     * Get suggested response to review
     */
    public function suggestResponse(Request $request)
    {
        $request->validate([
            'review_id' => 'required|exists:reviews,id',
        ]);

        $review = Review::findOrFail($request->input('review_id'));

        $suggestion = $this->sentimentService->suggestResponse([
            'text' => $review->review,
            'rating' => $review->rating,
            'sentiment' => $review->sentiment_label ?? 'neutral',
        ]);

        return response()->json([
            'success' => true,
            'data' => $suggestion,
        ]);
    }

    /**
     * Batch analyze product reviews
     */
    public function batchAnalyze(Request $request, $productId)
    {
        $reviews = Review::where('product_id', $productId)
            ->whereNull('sentiment_score')
            ->limit(100)
            ->get();

        $analyzed = 0;
        foreach ($reviews as $review) {
            $sentiment = $this->sentimentService->analyzeSentiment($review->review);
            
            $review->update([
                'sentiment_score' => $sentiment['score'],
                'sentiment_label' => $sentiment['label'],
            ]);

            $analyzed++;
        }

        return response()->json([
            'success' => true,
            'analyzed_count' => $analyzed,
        ]);
    }
}
