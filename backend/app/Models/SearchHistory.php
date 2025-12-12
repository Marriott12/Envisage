<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SearchHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'session_id',
        'search_query',
        'results_count',
        'had_results',
        'clicked_product_id',
    ];

    protected $casts = [
        'results_count' => 'integer',
        'had_results' => 'boolean',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function clickedProduct()
    {
        return $this->belongsTo(Product::class, 'clicked_product_id');
    }

    // Scopes
    public function scopeWithResults($query)
    {
        return $query->where('had_results', true);
    }

    public function scopeNoResults($query)
    {
        return $query->where('had_results', false);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // Helper methods
    public static function trackSearch($userId, $sessionId, $query, $resultsCount)
    {
        return self::create([
            'user_id' => $userId,
            'session_id' => $sessionId,
            'search_query' => $query,
            'results_count' => $resultsCount,
            'had_results' => $resultsCount > 0,
        ]);
    }

    public static function trackClick($searchId, $productId)
    {
        $search = self::find($searchId);
        if ($search) {
            $search->update(['clicked_product_id' => $productId]);
        }
    }

    public static function getPopularSearches($limit = 10, $days = 30)
    {
        return self::where('created_at', '>=', now()->subDays($days))
            ->where('had_results', true)
            ->selectRaw('search_query, COUNT(*) as search_count')
            ->groupBy('search_query')
            ->orderByDesc('search_count')
            ->limit($limit)
            ->pluck('search_count', 'search_query');
    }

    public static function getFailedSearches($limit = 10, $days = 30)
    {
        return self::where('created_at', '>=', now()->subDays($days))
            ->where('had_results', false)
            ->selectRaw('search_query, COUNT(*) as search_count')
            ->groupBy('search_query')
            ->orderByDesc('search_count')
            ->limit($limit)
            ->pluck('search_count', 'search_query');
    }

    public static function getUserSearchHistory($userId, $limit = 20)
    {
        return self::where('user_id', $userId)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    public static function getSearchBasedRecommendations($userId, $limit = 10)
    {
        // Get user's recent successful searches
        $searches = self::where('user_id', $userId)
            ->withResults()
            ->recent(30)
            ->pluck('search_query')
            ->unique();

        if ($searches->isEmpty()) {
            return collect();
        }

        // Find products matching these search terms
        $products = Product::where(function ($query) use ($searches) {
            foreach ($searches as $search) {
                $query->orWhere('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
            }
        })
        ->limit($limit)
        ->get();

        return $products;
    }
}
