<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\ReviewHelpfulness;
use App\Models\ReviewImage;
use App\Models\ReviewResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ReviewController extends Controller
{
    /**
     * Get reviews for a product
     */
    public function index(Request $request, $productId)
    {
        $perPage = $request->get('per_page', 10);
        $rating = $request->get('rating');
        $sortBy = $request->get('sort_by', 'recent'); // recent, helpful, rating_high, rating_low
        $verified = $request->get('verified_only', false);

        $query = ProductReview::with(['user', 'images', 'responses.user'])
            ->where('product_id', $productId)
            ->approved();

        if ($rating) {
            $query->rating($rating);
        }

        if ($verified) {
            $query->verified();
        }

        // Sort
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
            default:
                $query->orderBy('created_at', 'desc');
        }

        $reviews = $query->paginate($perPage);

        return response()->json($reviews);
    }

    /**
     * Get review statistics for a product
     */
    public function statistics($productId)
    {
        $stats = ProductReview::where('product_id', $productId)
            ->approved()
            ->selectRaw('
                COUNT(*) as total_reviews,
                AVG(rating) as average_rating,
                SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as five_star,
                SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as four_star,
                SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as three_star,
                SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as two_star,
                SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as one_star,
                SUM(CASE WHEN is_verified_purchase = 1 THEN 1 ELSE 0 END) as verified_purchases
            ')
            ->first();

        return response()->json($stats);
    }

    /**
     * Create a new review
     */
    public function store(Request $request, $productId)
    {
        $validator = Validator::make($request->all(), [
            'rating' => 'required|integer|min:1|max:5',
            'title' => 'required|string|max:255',
            'comment' => 'required|string|min:10',
            'order_id' => 'nullable|exists:orders,id',
            'images' => 'nullable|array|max:5',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:5120', // 5MB max
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = Auth::user();

        // Check if product exists
        $product = Product::findOrFail($productId);

        // Check if user already reviewed this product
        $existingReview = ProductReview::where('product_id', $productId)
            ->where('user_id', $user->id)
            ->first();

        if ($existingReview) {
            return response()->json(['error' => 'You have already reviewed this product'], 409);
        }

        // Check if verified purchase
        $isVerified = false;
        if ($request->order_id) {
            $isVerified = DB::table('orders')
                ->join('order_items', 'orders.id', '=', 'order_items.order_id')
                ->where('orders.id', $request->order_id)
                ->where('orders.user_id', $user->id)
                ->where('order_items.product_id', $productId)
                ->exists();
        }

        DB::beginTransaction();
        try {
            // Create review
            $review = ProductReview::create([
                'product_id' => $productId,
                'user_id' => $user->id,
                'order_id' => $request->order_id,
                'rating' => $request->rating,
                'title' => $request->title,
                'comment' => $request->comment,
                'is_verified_purchase' => $isVerified,
            ]);

            // Handle image uploads
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $index => $image) {
                    $path = $image->store('reviews', 'public');
                    
                    // Create thumbnail (optional - requires intervention/image package)
                    // For now, we'll skip thumbnail generation
                    
                    ReviewImage::create([
                        'review_id' => $review->id,
                        'image_path' => $path,
                        'sort_order' => $index,
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Review submitted successfully',
                'review' => $review->load('images'),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to submit review'], 500);
        }
    }

    /**
     * Update a review
     */
    public function update(Request $request, $reviewId)
    {
        $validator = Validator::make($request->all(), [
            'rating' => 'sometimes|integer|min:1|max:5',
            'title' => 'sometimes|string|max:255',
            'comment' => 'sometimes|string|min:10',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $review = ProductReview::findOrFail($reviewId);

        // Check ownership
        if ($review->user_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $review->update($request->only(['rating', 'title', 'comment']));

        return response()->json([
            'message' => 'Review updated successfully',
            'review' => $review,
        ]);
    }

    /**
     * Delete a review
     */
    public function destroy($reviewId)
    {
        $review = ProductReview::findOrFail($reviewId);

        // Check ownership or admin
        if ($review->user_id !== Auth::id() && !Auth::user()->hasRole('admin')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Delete images from storage
        foreach ($review->images as $image) {
            Storage::disk('public')->delete($image->image_path);
            if ($image->thumbnail_path) {
                Storage::disk('public')->delete($image->thumbnail_path);
            }
        }

        $review->delete();

        return response()->json(['message' => 'Review deleted successfully']);
    }

    /**
     * Mark review as helpful or not helpful
     */
    public function markHelpful(Request $request, $reviewId)
    {
        $validator = Validator::make($request->all(), [
            'is_helpful' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $review = ProductReview::findOrFail($reviewId);
        $user = Auth::user();

        // Check if user already voted
        $existingVote = ReviewHelpfulness::where('review_id', $reviewId)
            ->where('user_id', $user->id)
            ->first();

        DB::beginTransaction();
        try {
            if ($existingVote) {
                // Update vote
                if ($existingVote->is_helpful !== $request->is_helpful) {
                    // Remove old vote count
                    if ($existingVote->is_helpful) {
                        $review->decrement('helpful_count');
                    } else {
                        $review->decrement('not_helpful_count');
                    }

                    // Add new vote count
                    if ($request->is_helpful) {
                        $review->increment('helpful_count');
                    } else {
                        $review->increment('not_helpful_count');
                    }

                    $existingVote->update(['is_helpful' => $request->is_helpful]);
                }
            } else {
                // Create new vote
                ReviewHelpfulness::create([
                    'review_id' => $reviewId,
                    'user_id' => $user->id,
                    'is_helpful' => $request->is_helpful,
                ]);

                // Update count
                if ($request->is_helpful) {
                    $review->increment('helpful_count');
                } else {
                    $review->increment('not_helpful_count');
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Vote recorded successfully',
                'helpful_count' => $review->fresh()->helpful_count,
                'not_helpful_count' => $review->fresh()->not_helpful_count,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to record vote'], 500);
        }
    }

    /**
     * Add a response to a review (admin/seller only)
     */
    public function addResponse(Request $request, $reviewId)
    {
        $validator = Validator::make($request->all(), [
            'response' => 'required|string|min:10',
            'responder_type' => 'required|in:admin,seller',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = Auth::user();

        // Check if user has permission (admin or seller)
        if (!$user->hasRole('admin') && !$user->hasRole('seller')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $review = ProductReview::findOrFail($reviewId);

        $response = ReviewResponse::create([
            'review_id' => $reviewId,
            'user_id' => $user->id,
            'responder_type' => $request->responder_type,
            'response' => $request->response,
        ]);

        return response()->json([
            'message' => 'Response added successfully',
            'response' => $response->load('user'),
        ], 201);
    }

    /**
     * Get user's reviews
     */
    public function myReviews(Request $request)
    {
        $perPage = $request->get('per_page', 10);

        $reviews = ProductReview::with(['product', 'images'])
            ->where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json($reviews);
    }
}
