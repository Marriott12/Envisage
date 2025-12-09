<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FlashSale;
use App\Models\FlashSaleProduct;
use App\Models\FlashSalePurchase;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FlashSaleController extends Controller
{
    public function index(Request $request)
    {
        $query = FlashSale::where('is_active', true)
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>=', now());

        $flashSales = $query->with(['products.product'])
            ->orderBy('ends_at')
            ->paginate(10);

        return response()->json($flashSales);
    }

    public function show($id)
    {
        $flashSale = FlashSale::where('id', $id)
            ->where('is_active', true)
            ->with(['products.product.images'])
            ->firstOrFail();

        return response()->json(['flash_sale' => $flashSale]);
    }

    public function create(Request $request)
    {
        $this->middleware('auth:sanctum');

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'starts_at' => 'required|date',
            'ends_at' => 'required|date|after:starts_at',
            'banner_image' => 'nullable|image|max:2048',
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.sale_price' => 'required|numeric|min:0',
            'products.*.quantity_limit' => 'nullable|integer|min:1',
            'products.*.per_user_limit' => 'required|integer|min:1|max:10',
        ]);

        // TODO: Check if user has admin/seller permissions

        $bannerPath = null;
        if ($request->hasFile('banner_image')) {
            $bannerPath = $request->file('banner_image')->store('flash-sales/banners', 'public');
        }

        $flashSale = FlashSale::create([
            'name' => $request->name,
            'description' => $request->description,
            'starts_at' => $request->starts_at,
            'ends_at' => $request->ends_at,
            'is_active' => true,
            'banner_image' => $bannerPath,
            'total_quantity' => 0,
            'sold_quantity' => 0,
        ]);

        foreach ($request->products as $productData) {
            $product = Product::findOrFail($productData['product_id']);
            
            $discountPercentage = (($product->price - $productData['sale_price']) / $product->price) * 100;

            FlashSaleProduct::create([
                'flash_sale_id' => $flashSale->id,
                'product_id' => $product->id,
                'original_price' => $product->price,
                'sale_price' => $productData['sale_price'],
                'discount_percentage' => $discountPercentage,
                'quantity_limit' => $productData['quantity_limit'] ?? null,
                'quantity_sold' => 0,
                'per_user_limit' => $productData['per_user_limit'],
                'is_active' => true,
            ]);
        }

        return response()->json(['flash_sale' => $flashSale->load('products')], 201);
    }

    public function purchase(Request $request, $flashSaleProductId)
    {
        $this->middleware('auth:sanctum');

        $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        $userId = Auth::id();
        $flashSaleProduct = FlashSaleProduct::with('flashSale')
            ->findOrFail($flashSaleProductId);

        // Check if flash sale is active
        if (!$flashSaleProduct->flashSale->isActive()) {
            return response()->json([
                'message' => 'This flash sale is not active',
            ], 400);
        }

        // Check stock
        if (!$flashSaleProduct->hasStock()) {
            return response()->json([
                'message' => 'This product is sold out',
            ], 400);
        }

        // Check per-user limit
        if (!$flashSaleProduct->canUserPurchase($userId, $request->quantity)) {
            return response()->json([
                'message' => 'You have reached the purchase limit for this product',
            ], 400);
        }

        // Create purchase record (actual order creation should happen in checkout)
        $purchase = FlashSalePurchase::create([
            'flash_sale_id' => $flashSaleProduct->flash_sale_id,
            'flash_sale_product_id' => $flashSaleProduct->id,
            'user_id' => $userId,
            'quantity' => $request->quantity,
            'price_paid' => $flashSaleProduct->sale_price * $request->quantity,
        ]);

        // Update sold quantity
        $flashSaleProduct->increment('quantity_sold', $request->quantity);
        $flashSaleProduct->flashSale->increment('sold_quantity', $request->quantity);

        return response()->json([
            'purchase' => $purchase,
            'message' => 'Flash sale item reserved. Please complete checkout.',
        ], 201);
    }

    public function myPurchases()
    {
        $this->middleware('auth:sanctum');

        $userId = Auth::id();
        
        $purchases = FlashSalePurchase::where('user_id', $userId)
            ->with(['flashSale', 'flashSaleProduct.product'])
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json($purchases);
    }

    public function endSale($id)
    {
        $this->middleware('auth:sanctum');
        // TODO: Check admin permissions

        $flashSale = FlashSale::findOrFail($id);

        $flashSale->update([
            'is_active' => false,
            'ends_at' => now(),
        ]);

        return response()->json([
            'message' => 'Flash sale ended successfully',
        ]);
    }
}
