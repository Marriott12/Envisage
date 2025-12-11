<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SearchController extends Controller
{
    public function products(Request $request)
    {
        $query = Product::with(['seller', 'category']);

        // Text search
        if ($request->has('q') && !empty($request->q)) {
            $search = $request->q;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('tags', 'like', "%{$search}%");
            });
        }

        // Price range
        if ($request->has('min_price')) {
            $query->where('price', '>=', $request->min_price);
        }
        if ($request->has('max_price')) {
            $query->where('price', '<=', $request->max_price);
        }

        // Rating filter
        if ($request->has('min_rating')) {
            $query->where('average_rating', '>=', $request->min_rating);
        }

        // Category filter
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Seller filter
        if ($request->has('seller_id')) {
            $query->where('seller_id', $request->seller_id);
        }

        // Condition filter
        if ($request->has('condition')) {
            $query->where('condition', $request->condition);
        }

        // Status filter (default to active)
        $query->where('status', $request->status ?? 'active');

        // Sorting
        $sortBy = $request->sort_by ?? 'relevance';
        switch ($sortBy) {
            case 'price_low':
                $query->orderBy('price', 'asc');
                break;
            case 'price_high':
                $query->orderBy('price', 'desc');
                break;
            case 'newest':
                $query->orderBy('created_at', 'desc');
                break;
            case 'popular':
                $query->orderBy('views_count', 'desc');
                break;
            case 'rating':
                $query->orderBy('average_rating', 'desc');
                break;
            default:
                $query->orderBy('created_at', 'desc');
        }

        $products = $query->paginate($request->per_page ?? 24);

        return response()->json([
            'success' => true,
            'data' => $products,
        ]);
    }

    public function autocomplete(Request $request)
    {
        if (!$request->has('q') || empty($request->q)) {
            return response()->json(['success' => true, 'data' => []]);
        }

        $search = $request->q;
        
        $suggestions = Product::select('name', 'id')
            ->where('name', 'like', "%{$search}%")
            ->where('status', 'active')
            ->limit(10)
            ->get()
            ->map(function($product) {
                return [
                    'id' => $product->id,
                    'text' => $product->name,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $suggestions,
        ]);
    }

    public function suggestions(Request $request)
    {
        // Get popular search terms
        $popular = DB::table('search_history')
            ->select('query', DB::raw('COUNT(*) as count'))
            ->groupBy('query')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->pluck('query');

        return response()->json([
            'success' => true,
            'data' => $popular,
        ]);
    }

    public function history()
    {
        $history = DB::table('search_history')
            ->where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $history,
        ]);
    }
}
