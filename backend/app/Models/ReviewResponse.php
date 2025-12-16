<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReviewResponse extends Model
{
    use HasFactory;

    protected $fillable = [
        'review_id',
        'user_id',
        'responder_type',
        'response',
    ];

    /**
     * Get the review that owns the response.
     */
    public function review()
    {
        return $this->belongsTo(ProductReview::class, 'review_id');
    }

    /**
     * Get the user that responded.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
