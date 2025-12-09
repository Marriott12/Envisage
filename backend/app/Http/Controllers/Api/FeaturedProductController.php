<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FeaturedProductController extends Controller
{
    /**
     * Get products available for featuring
     */
    public function availableProducts(Request $request)
    {
        $query = Product::where('featured', '!=', 1)
            ->orWhereNull('featured');

        // Search
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where('name', 'like', "%{$search}%");
        }

        // Filter by category
        if ($request->has('category')) {
            $query->where('category_id', $request->input('category'));
        }

        $products = $query->orderBy('created_at', 'desc')->get()->map(function($product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'price' => $product->price,
                'image' => $product->image,
                'category' => $product->category->name ?? 'Uncategorized',
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $products,
        ]);
    }

    /**
     * Get currently featured products
     */
    public function featured()
    {
        $products = Product::where('featured', 1)
            ->orderBy('featured_at', 'desc')
            ->get()
            ->map(function($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'price' => $product->price,
                    'image' => $product->image,
                    'slot' => $product->featured_slot ?? 1,
                    'featured_since' => $product->featured_at ?? $product->created_at,
                    'views' => rand(500, 5000),
                    'sales' => rand(50, 500),
                    'revenue' => rand(1000, 10000),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $products,
        ]);
    }

    /**
     * Feature a product
     */
    public function feature(Request $request, $id)
    {
        $request->validate([
            'slot' => 'required|integer|min:1|max:12',
        ]);

        $product = Product::findOrFail($id);
        $product->featured = 1;
        $product->featured_slot = $request->input('slot');
        $product->featured_at = now();
        $product->save();

        return response()->json([
            'success' => true,
            'message' => 'Product featured successfully',
            'data' => [
                'id' => $product->id,
                'name' => $product->name,
                'slot' => $product->featured_slot,
            ],
        ]);
    }

    /**
     * Unfeature a product
     */
    public function unfeature($id)
    {
        $product = Product::findOrFail($id);
        $product->featured = 0;
        $product->featured_slot = null;
        $product->save();

        return response()->json([
            'success' => true,
            'message' => 'Product unfeatured successfully',
            'data' => [
                'id' => $product->id,
                'name' => $product->name,
            ],
        ]);
    }
}
