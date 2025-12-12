<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserProductInteraction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'product_id',
        'interaction_type',
        'interaction_weight',
        'rating',
        'interacted_at',
    ];

    protected $casts = [
        'rating' => 'decimal:2',
        'interacted_at' => 'datetime',
        'interaction_weight' => 'integer',
    ];

    // Interaction types and their weights
    const INTERACTION_WEIGHTS = [
        'view' => 1,
        'cart' => 3,
        'wishlist' => 5,
        'purchase' => 10,
        'rate' => 7,
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // Scopes
    public function scopeViews($query)
    {
        return $query->where('interaction_type', 'view');
    }

    public function scopeCarts($query)
    {
        return $query->where('interaction_type', 'cart');
    }

    public function scopeWishlists($query)
    {
        return $query->where('interaction_type', 'wishlist');
    }

    public function scopePurchases($query)
    {
        return $query->where('interaction_type', 'purchase');
    }

    public function scopeRatings($query)
    {
        return $query->where('interaction_type', 'rate');
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('interacted_at', '>=', now()->subDays($days));
    }

    // Helper methods
    public static function trackInteraction($userId, $productId, $type, $rating = null)
    {
        return self::create([
            'user_id' => $userId,
            'product_id' => $productId,
            'interaction_type' => $type,
            'interaction_weight' => self::INTERACTION_WEIGHTS[$type] ?? 1,
            'rating' => $rating,
            'interacted_at' => now(),
        ]);
    }

    public static function getUserInteractionScore($userId, $productId)
    {
        return self::where('user_id', $userId)
            ->where('product_id', $productId)
            ->sum('interaction_weight');
    }
}
