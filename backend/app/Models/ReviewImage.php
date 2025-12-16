<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReviewImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'review_id',
        'image_path',
        'thumbnail_path',
        'sort_order',
    ];

    /**
     * Get the review that owns the image.
     */
    public function review()
    {
        return $this->belongsTo(ProductReview::class, 'review_id');
    }

    /**
     * Get full URL for image
     */
    public function getImageUrlAttribute()
    {
        return asset('storage/' . $this->image_path);
    }

    /**
     * Get full URL for thumbnail
     */
    public function getThumbnailUrlAttribute()
    {
        return $this->thumbnail_path ? asset('storage/' . $this->thumbnail_path) : $this->image_url;
    }
}
