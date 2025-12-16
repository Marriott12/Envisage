<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductReview extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'product_id',
        'user_id',
        'order_id',
        'rating',
        'title',
        'comment',
        'is_verified_purchase',
        'is_approved',
        'is_featured',
        'helpful_count',
        'not_helpful_count',
    ];

    protected $casts = [
        'is_verified_purchase' => 'boolean',
        'is_approved' => 'boolean',
        'is_featured' => 'boolean',
    ];

    protected $appends = ['helpful_percentage'];

    /**
     * Get the product that owns the review.
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the user that wrote the review.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the order associated with the review.
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the images for the review.
     */
    public function images()
    {
        return $this->hasMany(ReviewImage::class, 'review_id')->orderBy('sort_order');
    }

    /**
     * Get the helpfulness votes for the review.
     */
    public function helpfulness()
    {
        return $this->hasMany(ReviewHelpfulness::class, 'review_id');
    }

    /**
     * Get the responses to the review.
     */
    public function responses()
    {
        return $this->hasMany(ReviewResponse::class, 'review_id')->orderBy('created_at');
    }

    /**
     * Get the video reviews for this review.
     */
    public function videoReviews()
    {
        return $this->hasMany(VideoReview::class);
    }

    /**
     * Calculate helpful percentage
     */
    public function getHelpfulPercentageAttribute()
    {
        $total = $this->helpful_count + $this->not_helpful_count;
        if ($total === 0) {
            return 0;
        }
        return round(($this->helpful_count / $total) * 100);
    }

    /**
     * Scope for approved reviews
     */
    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }

    /**
     * Scope for verified purchases
     */
    public function scopeVerified($query)
    {
        return $query->where('is_verified_purchase', true);
    }

    /**
     * Scope for featured reviews
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope for specific rating
     */
    public function scopeRating($query, $rating)
    {
        return $query->where('rating', $rating);
    }
}
