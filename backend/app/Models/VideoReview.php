<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VideoReview extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_review_id',
        'video_path',
        'thumbnail_path',
        'duration_seconds',
        'file_size_kb',
        'encoding_status',
        'views_count',
    ];

    protected $casts = [
        'duration_seconds' => 'integer',
        'file_size_kb' => 'integer',
        'views_count' => 'integer',
    ];

    /**
     * Get the review this video belongs to
     */
    public function productReview()
    {
        return $this->belongsTo(ProductReview::class);
    }

    /**
     * Increment views count
     */
    public function incrementViews()
    {
        $this->increment('views_count');
    }

    /**
     * Check if video is ready
     */
    public function isReady()
    {
        return $this->encoding_status === 'completed';
    }

    /**
     * Get video URL
     */
    public function getVideoUrl()
    {
        return asset('storage/' . $this->video_path);
    }

    /**
     * Get thumbnail URL
     */
    public function getThumbnailUrl()
    {
        if ($this->thumbnail_path) {
            return asset('storage/' . $this->thumbnail_path);
        }
        return null;
    }
}
