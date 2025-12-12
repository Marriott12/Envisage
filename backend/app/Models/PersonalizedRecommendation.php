<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PersonalizedRecommendation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'recommendation_type',
        'product_ids',
        'scores',
        'algorithm',
        'generated_at',
        'expires_at',
    ];

    protected $casts = [
        'product_ids' => 'array',
        'scores' => 'array',
        'generated_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeValid($query)
    {
        return $query->where('expires_at', '>', now());
    }

    public function scopeByType($query, $type)
    {
        return $query->where('recommendation_type', $type);
    }

    // Helper methods
    public static function getRecommendations($userId, $type, $limit = 10)
    {
        $recommendation = self::where('user_id', $userId)
            ->where('recommendation_type', $type)
            ->valid()
            ->latest('generated_at')
            ->first();

        if (!$recommendation) {
            return null;
        }

        $productIds = array_slice($recommendation->product_ids, 0, $limit);
        
        return Product::whereIn('id', $productIds)
            ->get()
            ->sortBy(function ($product) use ($productIds) {
                return array_search($product->id, $productIds);
            })
            ->values();
    }

    public static function cacheRecommendations($userId, $type, $productIds, $scores, $algorithm, $ttlHours = 24)
    {
        return self::updateOrCreate(
            [
                'user_id' => $userId,
                'recommendation_type' => $type,
            ],
            [
                'product_ids' => $productIds,
                'scores' => $scores,
                'algorithm' => $algorithm,
                'generated_at' => now(),
                'expires_at' => now()->addHours($ttlHours),
            ]
        );
    }

    public function isExpired()
    {
        return $this->expires_at < now();
    }
}
