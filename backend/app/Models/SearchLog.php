<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class SearchLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'query',
        'results_count',
        'filters',
        'sort_by',
        'clicked_product_id',
        'click_position',
        'session_id',
        'ip_address',
        'user_agent',
        'response_time_ms',
    ];

    protected $casts = [
        'results_count' => 'integer',
        'clicked_product_id' => 'integer',
        'click_position' => 'integer',
        'response_time_ms' => 'decimal:2',
    ];

    /**
     * Get the user who performed the search
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the clicked product
     */
    public function clickedProduct()
    {
        return $this->belongsTo(Product::class, 'clicked_product_id');
    }

    /**
     * Scope: Recent searches
     */
    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', Carbon::now()->subDays($days));
    }

    /**
     * Scope: Searches with clicks
     */
    public function scopeWithClicks($query)
    {
        return $query->whereNotNull('clicked_product_id');
    }

    /**
     * Scope: Searches with no results
     */
    public function scopeNoResults($query)
    {
        return $query->where('results_count', 0);
    }

    /**
     * Scope: Popular searches
     */
    public function scopePopular($query, $days = 7)
    {
        return $query->recent($days)
            ->selectRaw('LOWER(query) as normalized_query, COUNT(*) as search_count')
            ->groupBy('normalized_query')
            ->orderBy('search_count', 'desc');
    }

    /**
     * Log a search query
     */
    public static function logSearch($query, $resultsCount, $userId = null, $sessionId = null, $filters = null, $sortBy = null)
    {
        return static::create([
            'user_id' => $userId,
            'query' => $query,
            'results_count' => $resultsCount,
            'filters' => $filters ? json_encode($filters) : null,
            'sort_by' => $sortBy,
            'session_id' => $sessionId ?? request()->session()->getId(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Log a product click
     */
    public function logClick($productId, $position)
    {
        $this->clicked_product_id = $productId;
        $this->click_position = $position;
        $this->save();
    }

    /**
     * Get click-through rate for a query
     */
    public static function getClickThroughRate($query = null, $days = 30)
    {
        $searches = static::recent($days);
        
        if ($query) {
            $searches->whereRaw('LOWER(query) = ?', [strtolower($query)]);
        }

        $total = $searches->count();
        $withClicks = $searches->withClicks()->count();

        if ($total == 0) {
            return 0;
        }

        return round(($withClicks / $total) * 100, 2);
    }

    /**
     * Get most popular searches
     */
    public static function getPopularSearches($limit = 10, $days = 7)
    {
        return static::recent($days)
            ->selectRaw('LOWER(query) as query, COUNT(*) as count')
            ->groupBy('query')
            ->orderBy('count', 'desc')
            ->limit($limit)
            ->pluck('count', 'query');
    }

    /**
     * Get searches with no results
     */
    public static function getNoResultSearches($limit = 20, $days = 7)
    {
        return static::recent($days)
            ->noResults()
            ->selectRaw('LOWER(query) as query, COUNT(*) as count')
            ->groupBy('query')
            ->orderBy('count', 'desc')
            ->limit($limit)
            ->pluck('count', 'query');
    }

    /**
     * Get average response time
     */
    public static function getAverageResponseTime($days = 30)
    {
        return static::recent($days)
            ->whereNotNull('response_time_ms')
            ->avg('response_time_ms');
    }

    /**
     * Get search analytics
     */
    public static function getAnalytics($days = 30)
    {
        $totalSearches = static::recent($days)->count();
        $uniqueQueries = static::recent($days)
            ->selectRaw('COUNT(DISTINCT LOWER(query)) as count')
            ->value('count');

        return [
            'total_searches' => $totalSearches,
            'unique_queries' => $uniqueQueries,
            'avg_results_per_search' => static::recent($days)->avg('results_count'),
            'click_through_rate' => static::getClickThroughRate(null, $days),
            'no_result_rate' => $totalSearches > 0 ? 
                (static::recent($days)->noResults()->count() / $totalSearches) * 100 : 0,
            'avg_response_time_ms' => static::getAverageResponseTime($days),
            'popular_searches' => static::getPopularSearches(10, $days),
            'no_result_searches' => static::getNoResultSearches(10, $days),
        ];
    }
}
