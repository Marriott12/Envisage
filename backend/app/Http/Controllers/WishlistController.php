<?php

namespace App\Http\Controllers;

use App\Models\Wishlist;
use App\Models\WishlistItem;
use App\Models\PriceAlert;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class WishlistController extends Controller
{
    /**
     * Get all wishlists for authenticated user
     */
    public function index()
    {
        $wishlists = Wishlist::where('user_id', auth()->id())
            ->withCount('items')
            ->latest()
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => ['wishlists' => $wishlists]
        ]);
    }

    /**
     * Create a new wishlist
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_public' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $wishlist = Wishlist::create([
            'user_id' => auth()->id(),
            'name' => $request->name,
            'description' => $request->description,
            'is_public' => $request->is_public ?? false,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Wishlist created successfully',
            'data' => ['wishlist' => $wishlist]
        ], 201);
    }

    /**
     * Get a specific wishlist with items
     */
    public function show($id)
    {
        $wishlist = Wishlist::where('user_id', auth()->id())
            ->with(['items.product'])
            ->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => ['wishlist' => $wishlist]
        ]);
    }

    /**
     * Update wishlist
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_public' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $wishlist = Wishlist::where('user_id', auth()->id())
            ->findOrFail($id);

        $wishlist->update($request->only(['name', 'description', 'is_public']));

        return response()->json([
            'status' => 'success',
            'message' => 'Wishlist updated successfully',
            'data' => ['wishlist' => $wishlist]
        ]);
    }

    /**
     * Delete wishlist
     */
    public function destroy($id)
    {
        $wishlist = Wishlist::where('user_id', auth()->id())
            ->findOrFail($id);

        $wishlist->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Wishlist deleted successfully'
        ]);
    }

    /**
     * Add product to wishlist
     */
    public function addItem(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'priority' => 'integer|min:0|max:2',
            'notes' => 'nullable|string',
            'target_price' => 'nullable|numeric|min:0',
            'price_alert_enabled' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $wishlist = Wishlist::where('user_id', auth()->id())
            ->findOrFail($id);

        // Check if item already exists
        $existingItem = WishlistItem::where('wishlist_id', $wishlist->id)
            ->where('product_id', $request->product_id)
            ->first();

        if ($existingItem) {
            return response()->json([
                'status' => 'error',
                'message' => 'Product already in this wishlist'
            ], 409);
        }

        $item = WishlistItem::create([
            'wishlist_id' => $wishlist->id,
            'product_id' => $request->product_id,
            'priority' => $request->priority ?? 0,
            'notes' => $request->notes,
            'target_price' => $request->target_price,
            'price_alert_enabled' => $request->price_alert_enabled ?? false,
        ]);

        // Create price alert if enabled
        if ($request->price_alert_enabled && $request->target_price) {
            PriceAlert::create([
                'user_id' => auth()->id(),
                'product_id' => $request->product_id,
                'target_price' => $request->target_price,
                'is_active' => true,
            ]);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Product added to wishlist',
            'data' => ['item' => $item->load('product')]
        ], 201);
    }

    /**
     * Remove product from wishlist
     */
    public function removeItem($wishlistId, $itemId)
    {
        $wishlist = Wishlist::where('user_id', auth()->id())
            ->findOrFail($wishlistId);

        $item = WishlistItem::where('wishlist_id', $wishlist->id)
            ->findOrFail($itemId);

        $item->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Product removed from wishlist'
        ]);
    }

    /**
     * Update wishlist item
     */
    public function updateItem(Request $request, $wishlistId, $itemId)
    {
        $validator = Validator::make($request->all(), [
            'priority' => 'integer|min:0|max:2',
            'notes' => 'nullable|string',
            'target_price' => 'nullable|numeric|min:0',
            'price_alert_enabled' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $wishlist = Wishlist::where('user_id', auth()->id())
            ->findOrFail($wishlistId);

        $item = WishlistItem::where('wishlist_id', $wishlist->id)
            ->findOrFail($itemId);

        $item->update($request->only([
            'priority',
            'notes',
            'target_price',
            'price_alert_enabled',
        ]));

        // Update price alert
        if ($request->has('price_alert_enabled')) {
            if ($request->price_alert_enabled && $request->target_price) {
                PriceAlert::updateOrCreate(
                    [
                        'user_id' => auth()->id(),
                        'product_id' => $item->product_id,
                    ],
                    [
                        'target_price' => $request->target_price,
                        'is_active' => true,
                    ]
                );
            } else {
                PriceAlert::where('user_id', auth()->id())
                    ->where('product_id', $item->product_id)
                    ->update(['is_active' => false]);
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Wishlist item updated',
            'data' => ['item' => $item->load('product')]
        ]);
    }

    /**
     * Get shared wishlist by token
     */
    public function getShared($token)
    {
        $wishlist = Wishlist::where('share_token', $token)
            ->where('is_public', true)
            ->with(['items.product', 'user:id,name'])
            ->firstOrFail();

        return response()->json([
            'status' => 'success',
            'data' => ['wishlist' => $wishlist]
        ]);
    }

    /**
     * Get all price alerts for user
     */
    public function getPriceAlerts()
    {
        $alerts = PriceAlert::where('user_id', auth()->id())
            ->where('is_active', true)
            ->with('product')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => ['alerts' => $alerts]
        ]);
    }

    /**
     * Create or update price alert
     */
    public function setPriceAlert(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'target_price' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $alert = PriceAlert::updateOrCreate(
            [
                'user_id' => auth()->id(),
                'product_id' => $request->product_id,
            ],
            [
                'target_price' => $request->target_price,
                'is_active' => true,
                'is_triggered' => false,
            ]
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Price alert set successfully',
            'data' => ['alert' => $alert]
        ], 201);
    }

    /**
     * Delete price alert
     */
    public function deletePriceAlert($id)
    {
        $alert = PriceAlert::where('user_id', auth()->id())
            ->findOrFail($id);

        $alert->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Price alert deleted'
        ]);
    }
}
