<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RecentlyViewed extends Model
{
    use HasFactory;

    protected $table = 'recently_viewed';

    protected $fillable = [
        'user_id',
        'session_id',
        'product_id',
        'viewed_at',
        'view_count',
    ];

    protected $casts = [
        'viewed_at' => 'datetime',
    ];

    /**
     * Get the user that viewed the product.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the product that was viewed.
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Track a product view
     */
    public static function track($productId, $userId = null, $sessionId = null)
    {
        $data = [
            'product_id' => $productId,
            'user_id' => $userId,
            'session_id' => $sessionId,
            'viewed_at' => now(),
        ];

        $viewed = static::where('product_id', $productId)
            ->where(function ($query) use ($userId, $sessionId) {
                if ($userId) {
                    $query->where('user_id', $userId);
                } else {
                    $query->where('session_id', $sessionId);
                }
            })
            ->first();

        if ($viewed) {
            $viewed->increment('view_count');
            $viewed->update(['viewed_at' => now()]);
            return $viewed;
        }

        return static::create(array_merge($data, ['view_count' => 1]));
    }

    /**
     * Get recently viewed products for user or session
     */
    public static function getRecent($userId = null, $sessionId = null, $limit = 20)
    {
        return static::with('product')
            ->where(function ($query) use ($userId, $sessionId) {
                if ($userId) {
                    $query->where('user_id', $userId);
                } else {
                    $query->where('session_id', $sessionId);
                }
            })
            ->orderBy('viewed_at', 'desc')
            ->limit($limit)
            ->get();
    }
}
