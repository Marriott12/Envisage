<?php

namespace App\Services;

use App\Models\UserProductInteraction;
use App\Models\ProductSimilarity;
use App\Models\PersonalizedRecommendation;
use App\Models\CollaborativeFilteringData;
use App\Models\UserPreference;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class RecommendationService
{
    /**
     * Get personalized recommendations for a user
     * Uses hybrid collaborative + content-based filtering
     */
    public function getPersonalizedRecommendations($userId, $limit = 10)
    {
        // Check for cached recommendations
        $cached = PersonalizedRecommendation::getRecommendations($userId, 'for_you', $limit);
        if ($cached) {
            return $cached;
        }

        // Generate new recommendations
        $collaborativeRecs = $this->collaborativeFiltering($userId, $limit * 2);
        $contentRecs = $this->contentBasedFiltering($userId, $limit * 2);

        // Merge and score recommendations (hybrid approach)
        $hybridScores = [];
        
        foreach ($collaborativeRecs as $productId => $score) {
            $hybridScores[$productId] = $score * 0.6; // 60% weight to collaborative
        }

        foreach ($contentRecs as $productId => $score) {
            if (isset($hybridScores[$productId])) {
                $hybridScores[$productId] += $score * 0.4; // 40% weight to content
            } else {
                $hybridScores[$productId] = $score * 0.4;
            }
        }

        // Filter out already purchased products
        $purchasedIds = UserProductInteraction::where('user_id', $userId)
            ->where('interaction_type', 'purchase')
            ->pluck('product_id')
            ->toArray();

        $hybridScores = array_diff_key($hybridScores, array_flip($purchasedIds));

        // Sort by score and get top N
        arsort($hybridScores);
        $topProductIds = array_slice(array_keys($hybridScores), 0, $limit);
        $topScores = array_slice($hybridScores, 0, $limit);

        // Cache recommendations
        PersonalizedRecommendation::cacheRecommendations(
            $userId,
            'for_you',
            $topProductIds,
            $topScores,
            'hybrid',
            24
        );

        return Product::whereIn('id', $topProductIds)
            ->get()
            ->sortBy(function ($product) use ($topProductIds) {
                return array_search($product->id, $topProductIds);
            })
            ->values();
    }

    /**
     * Collaborative filtering: user-based
     * Find similar users and recommend what they liked
     */
    protected function collaborativeFiltering($userId, $limit)
    {
        // Get similar users
        $similarUserIds = CollaborativeFilteringData::getSimilarEntities('user_similarity', $userId, 20);
        
        if (empty($similarUserIds)) {
            return [];
        }

        // Get products these similar users liked
        $recommendations = UserProductInteraction::whereIn('user_id', $similarUserIds)
            ->where('interaction_type', '!=', 'view') // More meaningful interactions
            ->select('product_id', DB::raw('SUM(interaction_weight) as score'))
            ->groupBy('product_id')
            ->orderByDesc('score')
            ->limit($limit)
            ->pluck('score', 'product_id')
            ->toArray();

        // Normalize scores
        $maxScore = max($recommendations) ?: 1;
        foreach ($recommendations as $productId => $score) {
            $recommendations[$productId] = $score / $maxScore;
        }

        return $recommendations;
    }

    /**
     * Content-based filtering: recommend similar products to what user liked
     */
    protected function contentBasedFiltering($userId, $limit)
    {
        // Get user's preferences
        $preference = UserPreference::where('user_id', $userId)->first();
        
        if (!$preference) {
            return [];
        }

        $recommendations = [];

        // Get products from favorite categories
        if ($preference->favorite_categories) {
            $categoryIds = array_keys($preference->favorite_categories);
            $products = Product::whereIn('category_id', $categoryIds)
                ->limit($limit)
                ->pluck('id')
                ->toArray();

            foreach ($products as $productId) {
                $recommendations[$productId] = 0.5; // Base score
            }
        }

        // Boost products in user's price range
        if ($preference->price_range) {
            $priceMin = $preference->price_range['min'] ?? 0;
            $priceMax = $preference->price_range['max'] ?? 999999;

            $priceProducts = Product::whereBetween('price', [$priceMin, $priceMax])
                ->limit($limit)
                ->pluck('id')
                ->toArray();

            foreach ($priceProducts as $productId) {
                $recommendations[$productId] = ($recommendations[$productId] ?? 0) + 0.3;
            }
        }

        // Normalize scores
        if (!empty($recommendations)) {
            $maxScore = max($recommendations);
            foreach ($recommendations as $productId => $score) {
                $recommendations[$productId] = $score / $maxScore;
            }
        }

        return $recommendations;
    }

    /**
     * Get similar products based on collaborative filtering
     */
    public function getSimilarProducts($productId, $limit = 10)
    {
        // Check for pre-calculated similarities
        $similar = ProductSimilarity::getSimilarProducts($productId, $limit);
        
        if ($similar->count() > 0) {
            return $similar;
        }

        // Fallback: use collaborative filtering data
        $similarIds = CollaborativeFilteringData::getSimilarEntities('item_similarity', $productId, $limit);
        
        if (empty($similarIds)) {
            return collect();
        }

        return Product::whereIn('id', $similarIds)->get();
    }

    /**
     * Generate recommendations for all active users
     * Run this as a scheduled task
     */
    public function generateBulkRecommendations()
    {
        // Get users with recent activity (last 90 days)
        $activeUsers = UserProductInteraction::where('interacted_at', '>=', now()->subDays(90))
            ->distinct('user_id')
            ->pluck('user_id');

        $generated = 0;
        foreach ($activeUsers as $userId) {
            try {
                $this->getPersonalizedRecommendations($userId, 20);
                $generated++;
            } catch (\Exception $e) {
                // Log error but continue
                \Log::error("Failed to generate recommendations for user {$userId}: " . $e->getMessage());
            }
        }

        return $generated;
    }

    /**
     * Cold start problem: recommendations for new users
     */
    public function getColdStartRecommendations($limit = 10)
    {
        // Return trending + top rated products
        $trending = \App\Models\TrendingProduct::getTrendingProducts($limit / 2);
        
        $topRated = Product::where('rating', '>=', 4.5)
            ->orderByDesc('rating')
            ->orderByDesc('num_reviews')
            ->limit($limit / 2)
            ->get();

        return $trending->merge($topRated)->take($limit);
    }

    /**
     * Track user interaction for real-time learning
     */
    public function trackInteraction($userId, $productId, $type, $rating = null)
    {
        UserProductInteraction::trackInteraction($userId, $productId, $type, $rating);

        // Update user preferences asynchronously if significant interaction
        if (in_array($type, ['purchase', 'wishlist', 'rate'])) {
            \App\Jobs\UpdateUserPreferences::dispatch($userId)->delay(now()->addMinutes(5));
        }
    }
}
