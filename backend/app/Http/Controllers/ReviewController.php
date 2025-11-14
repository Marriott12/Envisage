<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\Product;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function getAllReviews()
    {
        $reviews = Review::with(['user:id,name,email', 'product:id,title'])
            ->latest()
            ->paginate(20);

        return response()->json([
            'status' => 'success',
            'data' => $reviews
        ]);
    }

    public function index($productId)
    {
        $product = Product::findOrFail($productId);
        
        $reviews = Review::where('product_id', $productId)
            ->with('user:id,name,email')
            ->latest()
            ->get();

        $averageRating = $reviews->avg('rating') ?? 0;
        $totalReviews = $reviews->count();

        return response()->json([
            'status' => 'success',
            'data' => [
                'reviews' => $reviews->map(function($review) {
                    return [
                        'id' => $review->id,
                        'rating' => $review->rating,
                        'review' => $review->review,
                        'verified_purchase' => $review->verified_purchase,
                        'user' => [
                            'name' => $review->user->name,
                            'email' => $review->user->email,
                        ],
                        'created_at' => $review->created_at->toISOString(),
                    ];
                }),
                'average_rating' => round($averageRating, 1),
                'total_reviews' => $totalReviews,
            ]
        ]);
    }

    public function store(Request $request, $productId)
    {
        $product = Product::findOrFail($productId);
        
        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'review' => 'required|string|min:10|max:1000',
        ]);

        // Check if user already reviewed this product
        $existingReview = Review::where('product_id', $productId)
            ->where('user_id', auth()->id())
            ->first();

        if ($existingReview) {
            return response()->json([
                'status' => 'error',
                'message' => 'You have already reviewed this product',
            ], 422);
        }

        $review = Review::create([
            'product_id' => $productId,
            'user_id' => auth()->id(),
            'rating' => $validated['rating'],
            'review' => $validated['review'],
            'verified_purchase' => false,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Review submitted successfully',
            'data' => [
                'review' => [
                    'id' => $review->id,
                    'rating' => $review->rating,
                    'review' => $review->review,
                    'created_at' => $review->created_at->toISOString(),
                ]
            ]
        ], 201);
    }
}
