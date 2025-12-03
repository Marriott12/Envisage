<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\Product;
use App\Models\Order;
use App\Models\ReviewHelpfulness;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReviewController extends Controller
{
    public function getAllReviews()
    {
        $reviews = Review::with(['user:id,name,email,avatar', 'product:id,title'])
            ->latest()
            ->paginate(20);

        return response()->json([
            'status' => 'success',
            'data' => $reviews
        ]);
    }

    public function index(Request $request, $productId)
    {
        $product = Product::findOrFail($productId);
        
        $perPage = $request->input('per_page', 10);
        $sortBy = $request->input('sort_by', 'recent'); 
        $ratingFilter = $request->input('rating');

        $query = Review::where('product_id', $productId)
            ->with('user:id,name,email,avatar');

        if ($ratingFilter) {
            $query->where('rating', $ratingFilter);
        }

        switch ($sortBy) {
            case 'helpful':
                $query->orderBy('helpful_count', 'desc');
                break;
            case 'rating_high':
                $query->orderBy('rating', 'desc');
                break;
            case 'rating_low':
                $query->orderBy('rating', 'asc');
                break;
            case 'recent':
            default:
                $query->orderBy('created_at', 'desc');
                break;
        }

        $reviews = $query->paginate($perPage);

        // Get rating statistics
        $stats = Review::where('product_id', $productId)
            ->select(
                DB::raw('COUNT(*) as total'),
                DB::raw('AVG(rating) as average'),
                DB::raw('SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as five_star'),
                DB::raw('SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as four_star'),
                DB::raw('SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as three_star'),
                DB::raw('SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as two_star'),
                DB::raw('SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as one_star')
            )
            ->first();

        return response()->json([
            'status' => 'success',
            'data' => [
                'reviews' => $reviews,
                'stats' => [
                    'total' => $stats->total ?? 0,
                    'average' => $stats->average ? round($stats->average, 1) : 0,
                    'distribution' => [
                        5 => $stats->five_star ?? 0,
                        4 => $stats->four_star ?? 0,
                        3 => $stats->three_star ?? 0,
                        2 => $stats->two_star ?? 0,
                        1 => $stats->one_star ?? 0,
                    ],
                ],
            ]
        ]);
    }

    public function store(Request $request, $productId)
    {
        $product = Product::findOrFail($productId);
        
        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'review' => 'nullable|string|max:2000',
            'title' => 'nullable|string|max:255',
            'comment' => 'nullable|string|max:2000',
            'images' => 'nullable|array|max:5',
            'images.*' => 'string',
            'order_id' => 'nullable|exists:orders,id',
        ]);

        $existingReview = Review::where('product_id', $productId)
            ->where('user_id', auth()->id())
            ->first();

        if ($existingReview) {
            return response()->json([
                'status' => 'error',
                'message' => 'You have already reviewed this product',
            ], 422);
        }

        // Check verified purchase
        $verifiedPurchase = Order::where('user_id', auth()->id())
            ->whereHas('items', function ($query) use ($productId) {
                $query->where('product_id', $productId);
            })
            ->where('status', '!=', 'cancelled')
            ->exists();

        $review = Review::create([
            'product_id' => $productId,
            'user_id' => auth()->id(),
            'order_id' => $validated['order_id'] ?? null,
            'rating' => $validated['rating'],
            'review' => $validated['review'] ?? $validated['comment'] ?? '',
            'title' => $validated['title'] ?? null,
            'comment' => $validated['comment'] ?? null,
            'images' => $validated['images'] ?? null,
            'verified_purchase' => $verifiedPurchase,
            'helpful_count' => 0,
            'not_helpful_count' => 0,
        ]);

        $this->updateProductRating($productId);

        return response()->json([
            'status' => 'success',
            'message' => 'Review submitted successfully',
            'data' => ['review' => $review->load('user:id,name,email,avatar')]
        ], 201);
    }

    public function update(Request $request, $productId, $reviewId)
    {
        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'review' => 'nullable|string|max:2000',
            'title' => 'nullable|string|max:255',
            'comment' => 'nullable|string|max:2000',
            'images' => 'nullable|array|max:5',
            'images.*' => 'string',
        ]);

        $review = Review::where('id', $reviewId)
            ->where('product_id', $productId)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $review->update([
            'rating' => $validated['rating'],
            'review' => $validated['review'] ?? $validated['comment'] ?? '',
            'title' => $validated['title'] ?? null,
            'comment' => $validated['comment'] ?? null,
            'images' => $validated['images'] ?? null,
        ]);

        $this->updateProductRating($productId);

        return response()->json([
            'status' => 'success',
            'message' => 'Review updated successfully',
            'data' => ['review' => $review->load('user:id,name,email,avatar')]
        ]);
    }

    public function destroy($productId, $reviewId)
    {
        $review = Review::where('id', $reviewId)
            ->where('product_id', $productId)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $review->delete();
        $this->updateProductRating($productId);

        return response()->json([
            'status' => 'success',
            'message' => 'Review deleted successfully'
        ]);
    }

    public function markHelpful(Request $request, $productId, $reviewId)
    {
        $validated = $request->validate([
            'is_helpful' => 'required|boolean',
        ]);

        $review = Review::where('id', $reviewId)
            ->where('product_id', $productId)
            ->firstOrFail();

        $existing = ReviewHelpfulness::where('user_id', auth()->id())
            ->where('review_id', $reviewId)
            ->first();

        if ($existing) {
            $oldValue = $existing->is_helpful;
            $existing->update(['is_helpful' => $validated['is_helpful']]);

            if ($oldValue !== $validated['is_helpful']) {
                if ($validated['is_helpful']) {
                    $review->increment('helpful_count');
                    $review->decrement('not_helpful_count');
                } else {
                    $review->decrement('helpful_count');
                    $review->increment('not_helpful_count');
                }
            }
        } else {
            ReviewHelpfulness::create([
                'user_id' => auth()->id(),
                'review_id' => $reviewId,
                'is_helpful' => $validated['is_helpful'],
            ]);

            if ($validated['is_helpful']) {
                $review->increment('helpful_count');
            } else {
                $review->increment('not_helpful_count');
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Feedback recorded',
            'data' => [
                'helpful_count' => $review->fresh()->helpful_count,
                'not_helpful_count' => $review->fresh()->not_helpful_count,
            ]
        ]);
    }

    public function getUserReview($productId)
    {
        $review = Review::where('user_id', auth()->id())
            ->where('product_id', $productId)
            ->with('user:id,name,email,avatar')
            ->first();

        return response()->json([
            'status' => 'success',
            'data' => ['review' => $review]
        ]);
    }

    public function canReview($productId)
    {
        $hasReviewed = Review::where('user_id', auth()->id())
            ->where('product_id', $productId)
            ->exists();

        if ($hasReviewed) {
            return response()->json([
                'status' => 'success',
                'data' => [
                    'can_review' => false,
                    'reason' => 'already_reviewed',
                ]
            ]);
        }

        $hasPurchased = Order::where('user_id', auth()->id())
            ->whereHas('items', function ($query) use ($productId) {
                $query->where('product_id', $productId);
            })
            ->where('status', '!=', 'cancelled')
            ->exists();

        return response()->json([
            'status' => 'success',
            'data' => [
                'can_review' => true,
                'has_purchased' => $hasPurchased,
            ]
        ]);
    }

    private function updateProductRating($productId)
    {
        $stats = Review::where('product_id', $productId)
            ->select(
                DB::raw('COUNT(*) as total'),
                DB::raw('AVG(rating) as average')
            )
            ->first();

        $product = Product::find($productId);
        if ($product) {
            $product->update([
                'rating' => $stats->average ? round($stats->average, 1) : 0,
                'reviews_count' => $stats->total ?? 0,
            ]);
        }
    }
}
