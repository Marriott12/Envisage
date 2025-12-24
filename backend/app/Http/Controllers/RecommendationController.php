<?php

namespace App\Http\Controllers;

use App\Services\RecommendationService;
use App\Services\TrendingService;
use App\Services\AdvancedRecommendationService;
use App\Models\FrequentlyBoughtTogether;
use App\Models\ProductSimilarity;
use App\Models\TrendingProduct;
use App\Models\RecommendationPerformance;
use App\Models\SearchHistory;
use App\Models\Product;
use App\Models\ProductView;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RecommendationController extends Controller
{
    protected $recommendationService;
    protected $trendingService;
    protected $advancedRecommendationService;

    public function __construct(
        RecommendationService $recommendationService, 
        TrendingService $trendingService,
        AdvancedRecommendationService $advancedRecommendationService
    ) {
        $this->recommendationService = $recommendationService;
        $this->trendingService = $trendingService;
        $this->advancedRecommendationService = $advancedRecommendationService;
    }

    /**
     * Get personalized recommendations for authenticated user
     */
    public function getPersonalized(Request $request)
    {
        $user = $request->user();
        $limit = $request->input('limit', 10);

        if (!$user) {
            // Cold start for guest users
            $products = $this->recommendationService->getColdStartRecommendations($limit);
            return response()->json([
                'type' => 'cold_start',
                'products' => $products,
                'message' => 'Sign in for personalized recommendations'
            ]);
        }

        $products = $this->recommendationService->getPersonalizedRecommendations($user->id, $limit);

        // Track impression
        RecommendationPerformance::trackImpression('for_you', 'hybrid');

        return response()->json([
            'type' => 'personalized',
            'products' => $products,
            'user_id' => $user->id
        ]);
    }

    /**
     * Get trending products
     */
    public function getTrending(Request $request)
    {
        $limit = $request->input('limit', 10);
        $hours = $request->input('hours', null);

        if ($hours) {
            // Real-time trending
            $products = $this->trendingService->getRealTimeTrending($hours, $limit);
            $type = 'realtime_trending';
        } else {
            // Daily trending
            $products = TrendingProduct::getTrendingProducts($limit);
            $type = 'trending';
        }

        // Track impression
        RecommendationPerformance::trackImpression($type, 'trending_algorithm');

        return response()->json([
            'type' => $type,
            'products' => $products
        ]);
    }

    /**
     * Get similar products
     */
    public function getSimilar(Request $request, $productId)
    {
        $limit = $request->input('limit', 10);

        $products = $this->recommendationService->getSimilarProducts($productId, $limit);

        // Track impression
        RecommendationPerformance::trackImpression('similar', 'collaborative');

        return response()->json([
            'type' => 'similar_products',
            'base_product_id' => $productId,
            'products' => $products
        ]);
    }

    /**
     * Get frequently bought together
     */
    public function getFrequentlyBoughtTogether(Request $request, $productId)
    {
        $limit = $request->input('limit', 5);

        $products = FrequentlyBoughtTogether::getFrequentlyBoughtWith($productId, $limit);

        // Track impression
        RecommendationPerformance::trackImpression('frequently_bought', 'association_rules');

        return response()->json([
            'type' => 'frequently_bought_together',
            'base_product_id' => $productId,
            'products' => $products
        ]);
    }

    /**
     * Get trending by category
     */
    public function getTrendingByCategory(Request $request, $categoryId)
    {
        $limit = $request->input('limit', 10);

        $products = $this->trendingService->getTrendingByCategory($categoryId, $limit);

        return response()->json([
            'type' => 'category_trending',
            'category_id' => $categoryId,
            'products' => $products
        ]);
    }

    /**
     * Get emerging trends
     */
    public function getEmergingTrends(Request $request)
    {
        $limit = $request->input('limit', 10);

        $products = $this->trendingService->getEmergingTrends($limit);

        return response()->json([
            'type' => 'emerging_trends',
            'products' => $products
        ]);
    }

    /**
     * Track user interaction with product
     */
    public function trackInteraction(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'interaction_type' => 'required|in:view,cart,wishlist,purchase,rate',
            'rating' => 'nullable|numeric|min:1|max:5',
            'recommendation_type' => 'nullable|string',
            'algorithm' => 'nullable|string',
        ]);

        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'User must be authenticated'], 401);
        }

        // Track interaction
        $this->recommendationService->trackInteraction(
            $user->id,
            $validated['product_id'],
            $validated['interaction_type'],
            $validated['rating'] ?? null
        );

        // Track click for recommendation performance
        if (isset($validated['recommendation_type']) && isset($validated['algorithm'])) {
            RecommendationPerformance::trackClick(
                $validated['recommendation_type'],
                $validated['algorithm']
            );

            // Track conversion if purchase
            if ($validated['interaction_type'] == 'purchase') {
                $product = Product::find($validated['product_id']);
                $revenue = $product ? $product->price : 0;
                
                RecommendationPerformance::trackConversion(
                    $validated['recommendation_type'],
                    $validated['algorithm'],
                    $revenue
                );
            }
        }

        return response()->json([
            'message' => 'Interaction tracked successfully',
            'type' => $validated['interaction_type']
        ]);
    }

    /**
     * Track search
     */
    public function trackSearch(Request $request)
    {
        $validated = $request->validate([
            'query' => 'required|string|max:255',
            'results_count' => 'required|integer|min:0',
            'clicked_product_id' => 'nullable|exists:products,id',
        ]);

        $user = $request->user();
        $userId = $user ? $user->id : null;
        $sessionId = $request->session()->getId();

        $search = SearchHistory::trackSearch(
            $userId,
            $sessionId,
            $validated['query'],
            $validated['results_count']
        );

        if (isset($validated['clicked_product_id'])) {
            SearchHistory::trackClick($search->id, $validated['clicked_product_id']);
        }

        return response()->json([
            'message' => 'Search tracked',
            'search_id' => $search->id
        ]);
    }

    /**
     * Get search-based recommendations
     */
    public function getSearchRecommendations(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'User must be authenticated'], 401);
        }

        $limit = $request->input('limit', 10);
        $products = SearchHistory::getSearchBasedRecommendations($user->id, $limit);

        return response()->json([
            'type' => 'search_based',
            'products' => $products
        ]);
    }

    /**
     * Get recommendation performance metrics
     */
    public function getPerformance(Request $request)
    {
        $type = $request->input('type');
        $days = $request->input('days', 30);

        $performance = RecommendationPerformance::getPerformanceReport($type, $days);

        return response()->json([
            'period_days' => $days,
            'performance' => $performance
        ]);
    }

    /**
     * Get popular searches
     */
    public function getPopularSearches(Request $request)
    {
        $limit = $request->input('limit', 10);
        $days = $request->input('days', 30);

        $popularSearches = SearchHistory::getPopularSearches($limit, $days);

        return response()->json([
            'period_days' => $days,
            'searches' => $popularSearches
        ]);
    }

    /**
     * Get failed searches (no results)
     */
    public function getFailedSearches(Request $request)
    {
        $limit = $request->input('limit', 10);
        $days = $request->input('days', 30);

        $failedSearches = SearchHistory::getFailedSearches($limit, $days);

        return response()->json([
            'period_days' => $days,
            'searches' => $failedSearches,
            'message' => 'Consider adding products for these searches'
        ]);
    }

    // Legacy method - Track product view
    public function trackView(Request $request, $productId)
    {
        $product = Product::findOrFail($productId);

        ProductView::create([
            'product_id' => $productId,
            'user_id' => $request->user() ? $request->user()->id : null,
            'session_id' => session()->getId(),
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'View tracked',
        ]);
    }

    // Get personalized recommendations for user
    public function getRecommendations(Request $request)
    {
        $userId = $request->user() ? $request->user()->id : null;
        $limit = $request->input('limit', 10);

        if ($userId) {
            // Personalized recommendations for logged-in users
            $recommendations = $this->getPersonalizedRecommendations($userId, $limit);
        } else {
            // Popular products for guests
            $recommendations = $this->getPopularProducts($limit);
        }

        return response()->json([
            'success' => true,
            'data' => $recommendations,
        ]);
    }

    // Get "Customers Also Viewed" for a product
    public function getAlsoViewed(Request $request, $productId)
    {
        $limit = $request->input('limit', 6);

        // Get products viewed by users who also viewed this product
        $alsoViewed = DB::table('product_views as pv1')
            ->join('product_views as pv2', 'pv1.user_id', '=', 'pv2.user_id')
            ->join('products', 'pv2.product_id', '=', 'products.id')
            ->where('pv1.product_id', $productId)
            ->where('pv2.product_id', '!=', $productId)
            ->whereNotNull('pv1.user_id')
            ->select('products.*', DB::raw('COUNT(DISTINCT pv1.user_id) as view_count'))
            ->groupBy('products.id')
            ->orderBy('view_count', 'desc')
            ->limit($limit)
            ->get();

        // If not enough, fill with same category products
        if ($alsoViewed->count() < $limit) {
            $product = Product::find($productId);
            $additional = Product::where('category_id', $product->category_id)
                ->where('id', '!=', $productId)
                ->whereNotIn('id', $alsoViewed->pluck('id'))
                ->inRandomOrder()
                ->limit($limit - $alsoViewed->count())
                ->get();

            $alsoViewed = $alsoViewed->merge($additional);
        }

        return response()->json([
            'success' => true,
            'data' => $alsoViewed,
        ]);
    }

    // Get "Customers Also Bought" for a product
    public function getAlsoBought(Request $request, $productId)
    {
        $limit = $request->input('limit', 6);

        // Get products bought together with this product
        $alsoBought = DB::table('order_items as oi1')
            ->join('order_items as oi2', 'oi1.order_id', '=', 'oi2.order_id')
            ->join('products', 'oi2.product_id', '=', 'products.id')
            ->where('oi1.product_id', $productId)
            ->where('oi2.product_id', '!=', $productId)
            ->select('products.*', DB::raw('COUNT(DISTINCT oi1.order_id) as purchase_count'))
            ->groupBy('products.id')
            ->orderBy('purchase_count', 'desc')
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $alsoBought,
        ]);
    }

    // Get "Similar Products" based on category and attributes
    public function getSimilarProducts(Request $request, $productId)
    {
        $limit = $request->input('limit', 6);
        $product = Product::findOrFail($productId);

        // Get products from same category
        $similar = Product::where('category_id', $product->category_id)
            ->where('id', '!=', $productId)
            ->where('price', '>=', $product->price * 0.7) // Price range ±30%
            ->where('price', '<=', $product->price * 1.3)
            ->orderByRaw('ABS(price - ?) ASC', [$product->price])
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $similar,
        ]);
    }

    // Get "Based on Your History" recommendations
    public function getHistoryBased(Request $request)
    {
        $userId = $request->user()->id;
        $limit = $request->input('limit', 10);

        // Get categories user has viewed/purchased
        $viewedCategories = ProductView::where('user_id', $userId)
            ->join('products', 'product_views.product_id', '=', 'products.id')
            ->select('products.category_id', DB::raw('COUNT(*) as view_count'))
            ->groupBy('products.category_id')
            ->orderBy('view_count', 'desc')
            ->limit(3)
            ->pluck('products.category_id');

        // Get products from those categories not yet viewed/purchased
        $viewedProductIds = ProductView::where('user_id', $userId)->pluck('product_id');
        $purchasedProductIds = Order::where('user_id', $userId)
            ->join('order_items', 'orders.id', '=', 'order_items.order_id')
            ->pluck('order_items.product_id');

        $excludedIds = $viewedProductIds->merge($purchasedProductIds)->unique();

        $recommendations = Product::whereIn('category_id', $viewedCategories)
            ->whereNotIn('id', $excludedIds)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $recommendations,
        ]);
    }

    // Legacy method: Get "New Arrivals" for user's preferred categories
    public function getNewArrivals(Request $request)
    {
        $limit = $request->input('limit', 10);
        $userId = $request->user() ? $request->user()->id : null;

        if ($userId) {
            // Get user's preferred categories
            $preferredCategories = ProductView::where('user_id', $userId)
                ->join('products', 'product_views.product_id', '=', 'products.id')
                ->select('products.category_id')
                ->groupBy('products.category_id')
                ->orderByRaw('COUNT(*) DESC')
                ->limit(3)
                ->pluck('products.category_id');

            if ($preferredCategories->isNotEmpty()) {
                $newProducts = Product::whereIn('category_id', $preferredCategories)
                    ->orderBy('created_at', 'desc')
                    ->limit($limit)
                    ->get();

                return response()->json([
                    'success' => true,
                    'data' => $newProducts,
                ]);
            }
        }

        // Default: latest products across all categories
        $newProducts = Product::orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $newProducts,
        ]);
    }

    // ==================== ADVANCED AI RECOMMENDATION METHODS ====================

    /**
     * Get neural collaborative filtering recommendations
     */
    public function neural(Request $request)
    {
        $userId = $request->user()->id ?? null;
        $limit = $request->input('limit', 20);
        $context = $request->input('context', []);

        $recommendations = $this->advancedRecommendationService->getNeuralRecommendations($userId, $limit, $context);

        return response()->json([
            'success' => true,
            'data' => $recommendations,
            'algorithm' => 'neural_collaborative_filtering',
        ]);
    }

    /**
     * Get multi-armed bandit recommendations
     */
    public function bandit(Request $request)
    {
        $userId = $request->user()->id ?? null;
        $limit = $request->input('limit', 20);

        $recommendations = $this->advancedRecommendationService->getBanditRecommendations($userId, $limit);

        return response()->json([
            'success' => true,
            'data' => $recommendations,
            'algorithm' => 'thompson_sampling',
        ]);
    }

    /**
     * Get session-based recommendations (GRU4Rec)
     */
    public function session(Request $request)
    {
        $sessionId = $request->input('session_id', session()->getId());
        $viewedProducts = $request->input('viewed_products', []);
        $limit = $request->input('limit', 10);

        $recommendations = $this->advancedRecommendationService->getSessionBasedRecommendations($sessionId, $viewedProducts, $limit);

        return response()->json([
            'success' => true,
            'data' => $recommendations,
            'algorithm' => 'gru4rec',
        ]);
    }

    /**
     * Get context-aware recommendations
     */
    public function contextAware(Request $request)
    {
        $userId = $request->user()->id ?? null;
        $limit = $request->input('limit', 20);

        $recommendations = $this->advancedRecommendationService->getContextAwareRecommendations($userId, $limit);

        return response()->json([
            'success' => true,
            'data' => $recommendations,
            'algorithm' => 'context_aware',
        ]);
    }

    /**
     * Get recommendations with A/B testing
     */
    public function withExperiment(Request $request)
    {
        $userId = $request->user()->id ?? null;
        $limit = $request->input('limit', 20);

        $recommendations = $this->advancedRecommendationService->getRecommendationsWithExperiment($userId, $limit);

        return response()->json([
            'success' => true,
            'data' => $recommendations,
        ]);
    }

    /**
     * Update recommendations with user feedback
     */
    public function updateFeedback(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'action' => 'required|in:click,view,purchase,wishlist,rate',
            'context' => 'array',
        ]);

        $userId = $request->user()->id;
        $productId = $request->input('product_id');
        $action = $request->input('action');
        $context = $request->input('context', []);

        $this->advancedRecommendationService->updateWithFeedback($userId, $productId, $action, $context);

        return response()->json([
            'success' => true,
            'message' => 'Feedback recorded successfully',
        ]);
    }
}
