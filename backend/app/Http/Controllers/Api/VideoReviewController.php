<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VideoReview;
use App\Models\ProductReview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class VideoReviewController extends Controller
{
    /**
     * Upload a video review
     */
    public function upload(Request $request)
    {
        $request->validate([
            'review_id' => 'required|exists:product_reviews,id',
            'video' => 'required|file|mimes:mp4,mov,avi,wmv|max:102400', // 100MB max
        ]);

        $review = ProductReview::findOrFail($request->review_id);

        // Verify review belongs to authenticated user
        if ($review->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        // Check if review already has a video
        if ($review->has_video) {
            return response()->json([
                'success' => false,
                'message' => 'Review already has a video',
            ], 400);
        }

        try {
            $video = $request->file('video');
            $filename = Str::uuid() . '.' . $video->getClientOriginalExtension();
            $path = $video->storeAs('reviews/videos', $filename, 'public');

            // Get file size in KB
            $fileSizeKb = round($video->getSize() / 1024);

            // Create video review record
            $videoReview = VideoReview::create([
                'product_review_id' => $review->id,
                'video_path' => $path,
                'file_size_kb' => $fileSizeKb,
                'encoding_status' => 'completed', // In production, this would be 'pending'
            ]);

            // Update review to indicate it has a video
            $review->update(['has_video' => true]);

            return response()->json([
                'success' => true,
                'message' => 'Video uploaded successfully',
                'video_review' => $videoReview,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload video: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get video for a review
     */
    public function show($reviewId)
    {
        $review = ProductReview::findOrFail($reviewId);
        $videoReview = VideoReview::where('product_review_id', $review->id)->first();

        if (!$videoReview) {
            return response()->json([
                'success' => false,
                'message' => 'No video found for this review',
            ], 404);
        }

        // Increment view count
        $videoReview->incrementViews();

        return response()->json([
            'success' => true,
            'video_review' => [
                'id' => $videoReview->id,
                'video_url' => $videoReview->getVideoUrl(),
                'thumbnail_url' => $videoReview->getThumbnailUrl(),
                'duration_seconds' => $videoReview->duration_seconds,
                'views_count' => $videoReview->views_count,
                'is_ready' => $videoReview->isReady(),
            ],
        ]);
    }

    /**
     * Delete a video review
     */
    public function destroy($id)
    {
        $videoReview = VideoReview::findOrFail($id);
        $review = $videoReview->productReview;

        // Verify review belongs to authenticated user or user is admin
        if ($review->user_id !== Auth::id() && !Auth::user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        try {
            // Delete video file from storage
            if (Storage::disk('public')->exists($videoReview->video_path)) {
                Storage::disk('public')->delete($videoReview->video_path);
            }

            // Delete thumbnail if exists
            if ($videoReview->thumbnail_path && Storage::disk('public')->exists($videoReview->thumbnail_path)) {
                Storage::disk('public')->delete($videoReview->thumbnail_path);
            }

            // Update review
            $review->update(['has_video' => false]);

            // Delete video review record
            $videoReview->delete();

            return response()->json([
                'success' => true,
                'message' => 'Video deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete video: ' . $e->getMessage(),
            ], 500);
        }
    }
}
