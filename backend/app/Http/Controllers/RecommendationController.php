<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductView;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RecommendationController extends Controller
{
    // Track product view
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

    // Get "Trending Now" products
    public function getTrending(Request $request)
    {
        $limit = $request->input('limit', 10);
        $days = $request->input('days', 7);

        $trending = Product::join('product_views', 'products.id', '=', 'product_views.product_id')
            ->where('product_views.created_at', '>=', now()->subDays($days))
            ->select('products.*', DB::raw('COUNT(product_views.id) as view_count'))
            ->groupBy('products.id')
            ->orderBy('view_count', 'desc')
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $trending,
        ]);
    }

    // Get "New Arrivals" for user's preferred categories
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

    // Private helper: Get personalized recommendations
    private function getPersonalizedRecommendations($userId, $limit)
    {
        // 1. Get user's purchase history categories
        $purchasedCategories = Order::where('user_id', $userId)
            ->join('order_items', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->select('products.category_id', DB::raw('COUNT(*) as purchase_count'))
            ->groupBy('products.category_id')
            ->orderBy('purchase_count', 'desc')
            ->limit(3)
            ->pluck('products.category_id');

        // 2. Get recently viewed categories
        $viewedCategories = ProductView::where('user_id', $userId)
            ->join('products', 'product_views.product_id', '=', 'products.id')
            ->select('products.category_id', DB::raw('COUNT(*) as view_count'))
            ->groupBy('products.category_id')
            ->orderBy('view_count', 'desc')
            ->limit(3)
            ->pluck('products.category_id');

        $preferredCategories = $purchasedCategories->merge($viewedCategories)->unique();

        // 3. Exclude already purchased and recently viewed
        $excludedIds = ProductView::where('user_id', $userId)
            ->where('created_at', '>=', now()->subDays(7))
            ->pluck('product_id');

        // 4. Get recommendations
        $recommendations = Product::whereIn('category_id', $preferredCategories)
            ->whereNotIn('id', $excludedIds)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        return $recommendations;
    }

    // Private helper: Get popular products
    private function getPopularProducts($limit)
    {
        return Product::join('product_views', 'products.id', '=', 'product_views.product_id')
            ->where('product_views.created_at', '>=', now()->subDays(30))
            ->select('products.*', DB::raw('COUNT(product_views.id) as view_count'))
            ->groupBy('products.id')
            ->orderBy('view_count', 'desc')
            ->limit($limit)
            ->get();
    }
}
