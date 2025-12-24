<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SentimentCache extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'overall_sentiment',
        'sentiment_distribution',
        'ai_summary',
        'key_themes',
        'aspect_sentiments',
        'review_count',
        'fake_review_count',
        'last_analyzed_at',
    ];

    protected $casts = [
        'overall_sentiment' => 'decimal:2',
        'sentiment_distribution' => 'array',
        'key_themes' => 'array',
        'aspect_sentiments' => 'array',
        'last_analyzed_at' => 'datetime',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function scopePositive($query)
    {
        return $query->where('overall_sentiment', '>', 0);
    }

    public function scopeNegative($query)
    {
        return $query->where('overall_sentiment', '<', 0);
    }

    public function scopeWithFakeReviews($query)
    {
        return $query->where('fake_review_count', '>', 0);
    }
}
