<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductBundle;
use App\Models\BundleProduct;
use App\Models\FrequentlyBoughtTogether;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BundleController extends Controller
{
    public function index(Request $request)
    {
        $query = ProductBundle::where('is_active', true);

        if ($request->seller_id) {
            $query->where('seller_id', $request->seller_id);
        }

        $bundles = $query->with(['seller', 'products'])
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json($bundles);
    }

    public function show($id)
    {
        $bundle = ProductBundle::where('id', $id)
            ->with(['seller', 'products', 'bundleProducts'])
            ->firstOrFail();

        return response()->json(['bundle' => $bundle]);
    }

    public function create(Request $request)
    {
        $this->middleware('auth:sanctum');

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|image|max:2048',
            'discount_type' => 'required|in:percentage,fixed',
            'discount_value' => 'required|numeric|min:0',
            'products' => 'required|array|min:2',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|integer|min:1',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after:starts_at',
        ]);

        $userId = Auth::id();
        
        // Verify all products belong to the seller
        $productIds = collect($request->products)->pluck('product_id');
        $invalidProducts = Product::whereIn('id', $productIds)
            ->where('seller_id', '!=', $userId)
            ->exists();

        if ($invalidProducts) {
            return response()->json([
                'message' => 'You can only bundle your own products',
            ], 403);
        }

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('bundles/images', 'public');
        }

        $bundle = ProductBundle::create([
            'name' => $request->name,
            'description' => $request->description,
            'seller_id' => $userId,
            'image' => $imagePath,
            'discount_type' => $request->discount_type,
            'discount_value' => $request->discount_value,
            'is_active' => true,
            'starts_at' => $request->starts_at,
            'ends_at' => $request->ends_at,
        ]);

        foreach ($request->products as $productData) {
            $product = Product::findOrFail($productData['product_id']);
            
            BundleProduct::create([
                'bundle_id' => $bundle->id,
                'product_id' => $product->id,
                'quantity' => $productData['quantity'],
                'price_at_time' => $product->price * $productData['quantity'],
            ]);
        }

        $bundle->calculatePrices();

        return response()->json(['bundle' => $bundle->load('products')], 201);
    }

    public function update(Request $request, $id)
    {
        $this->middleware('auth:sanctum');

        $request->validate([
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'discount_type' => 'nullable|in:percentage,fixed',
            'discount_value' => 'nullable|numeric|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $bundle = ProductBundle::findOrFail($id);

        if ($bundle->seller_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $bundle->update($request->only([
            'name', 'description', 'discount_type', 'discount_value', 'is_active'
        ]));

        if ($request->has('discount_value') || $request->has('discount_type')) {
            $bundle->calculatePrices();
        }

        return response()->json(['bundle' => $bundle]);
    }

    public function delete($id)
    {
        $this->middleware('auth:sanctum');

        $bundle = ProductBundle::findOrFail($id);

        if ($bundle->seller_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $bundle->delete();

        return response()->json(['message' => 'Bundle deleted successfully']);
    }

    public function frequentlyBoughtTogether($productId)
    {
        $recommendations = FrequentlyBoughtTogether::where('product_id', $productId)
            ->with('relatedProduct')
            ->orderByDesc('confidence_score')
            ->limit(5)
            ->get();

        return response()->json(['recommendations' => $recommendations]);
    }

    public function updateFrequentlyBought($orderId)
    {
        // This should be called after order completion
        $order = \App\Models\Order::with('items')->findOrFail($orderId);
        
        $productIds = collect($order->items)->pluck('product_id')->toArray();
        
        // Update frequently bought together for each product pair
        for ($i = 0; $i < count($productIds); $i++) {
            for ($j = $i + 1; $j < count($productIds); $j++) {
                $this->incrementFrequentlyBought($productIds[$i], $productIds[$j]);
                $this->incrementFrequentlyBought($productIds[$j], $productIds[$i]);
            }
        }
    }

    protected function incrementFrequentlyBought($productId, $relatedProductId)
    {
        $record = FrequentlyBoughtTogether::firstOrCreate([
            'product_id' => $productId,
            'related_product_id' => $relatedProductId,
        ], [
            'times_bought_together' => 0,
            'confidence_score' => 0,
        ]);

        $record->increment('times_bought_together');
        
        // Calculate confidence score (simplified)
        $totalOrders = DB::table('order_items')
            ->where('product_id', $productId)
            ->distinct('order_id')
            ->count();
        
        $confidence = $totalOrders > 0 ? ($record->times_bought_together / $totalOrders) : 0;
        $record->update(['confidence_score' => $confidence]);
    }
}
