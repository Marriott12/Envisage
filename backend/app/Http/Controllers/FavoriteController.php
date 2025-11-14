<?php

namespace App\Http\Controllers;

use App\Models\Favorite;
use App\Models\Product;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    /**
     * Get user's favorites
     */
    public function index()
    {
        $favorites = Favorite::where('user_id', auth()->id())
            ->with('product.seller:id,name,email')
            ->latest()
            ->get();

        return response()->json($favorites);
    }

    /**
     * Add product to favorites
     */
    public function store($productId)
    {
        $product = Product::findOrFail($productId);

        $favorite = Favorite::firstOrCreate([
            'user_id' => auth()->id(),
            'product_id' => $productId,
        ]);

        return response()->json([
            'message' => 'Added to favorites',
            'favorite' => $favorite->load('product')
        ], 201);
    }

    /**
     * Remove product from favorites
     */
    public function destroy($productId)
    {
        Favorite::where('user_id', auth()->id())
            ->where('product_id', $productId)
            ->delete();

        return response()->json(['message' => 'Removed from favorites']);
    }

    /**
     * Check if product is favorited
     */
    public function check($productId)
    {
        $isFavorited = Favorite::where('user_id', auth()->id())
            ->where('product_id', $productId)
            ->exists();

        return response()->json(['is_favorited' => $isFavorited]);
    }
}
