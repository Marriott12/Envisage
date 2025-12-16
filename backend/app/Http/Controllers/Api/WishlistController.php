<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Wishlist;
use App\Models\WishlistItem;
use App\Models\WishlistShare;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class WishlistController extends Controller
{
    /**
     * Get user's wishlists
     */
    public function index(Request $request)
    {
        $wishlists = Wishlist::with(['items.product'])
            ->where('user_id', Auth::id())
            ->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($wishlists);
    }

    /**
     * Create a new wishlist
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'privacy' => 'sometimes|in:private,public,shared',
            'is_default' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = Auth::user();

        DB::beginTransaction();
        try {
            // If setting as default, unset other defaults
            if ($request->is_default) {
                Wishlist::where('user_id', $user->id)
                    ->update(['is_default' => false]);
            }

            $wishlist = Wishlist::create([
                'user_id' => $user->id,
                'name' => $request->name,
                'description' => $request->description,
                'privacy' => $request->privacy ?? 'private',
                'is_default' => $request->is_default ?? false,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Wishlist created successfully',
                'wishlist' => $wishlist,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to create wishlist'], 500);
        }
    }

    /**
     * Get a specific wishlist
     */
    public function show($wishlistId)
    {
        $wishlist = Wishlist::with(['items.product', 'shares'])
            ->findOrFail($wishlistId);

        // Check ownership
        if ($wishlist->user_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return response()->json($wishlist);
    }

    /**
     * Update a wishlist
     */
    public function update(Request $request, $wishlistId)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'privacy' => 'sometimes|in:private,public,shared',
            'is_default' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $wishlist = Wishlist::findOrFail($wishlistId);

        // Check ownership
        if ($wishlist->user_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        DB::beginTransaction();
        try {
            // If setting as default, unset other defaults
            if ($request->is_default && !$wishlist->is_default) {
                Wishlist::where('user_id', Auth::id())
                    ->where('id', '!=', $wishlistId)
                    ->update(['is_default' => false]);
            }

            // Generate share token if changing to public/shared and doesn't have one
            if (in_array($request->privacy, ['public', 'shared']) && !$wishlist->share_token) {
                $request->merge(['share_token' => Str::random(32)]);
            }

            $wishlist->update($request->only(['name', 'description', 'privacy', 'is_default', 'share_token']));

            DB::commit();

            return response()->json([
                'message' => 'Wishlist updated successfully',
                'wishlist' => $wishlist,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to update wishlist'], 500);
        }
    }

    /**
     * Delete a wishlist
     */
    public function destroy($wishlistId)
    {
        $wishlist = Wishlist::findOrFail($wishlistId);

        // Check ownership
        if ($wishlist->user_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Prevent deleting default wishlist if it's the only one
        if ($wishlist->is_default) {
            $count = Wishlist::where('user_id', Auth::id())->count();
            if ($count === 1) {
                return response()->json(['error' => 'Cannot delete your only wishlist'], 400);
            }

            // Set another wishlist as default
            Wishlist::where('user_id', Auth::id())
                ->where('id', '!=', $wishlistId)
                ->first()
                ->update(['is_default' => true]);
        }

        $wishlist->delete();

        return response()->json(['message' => 'Wishlist deleted successfully']);
    }

    /**
     * Add product to wishlist
     */
    public function addItem(Request $request, $wishlistId)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'note' => 'nullable|string',
            'priority' => 'sometimes|integer|min:0|max:2',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $wishlist = Wishlist::findOrFail($wishlistId);

        // Check ownership
        if ($wishlist->user_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $product = Product::findOrFail($request->product_id);

        // Check if already in wishlist
        $existing = WishlistItem::where('wishlist_id', $wishlistId)
            ->where('product_id', $request->product_id)
            ->first();

        if ($existing) {
            return response()->json(['error' => 'Product already in wishlist'], 409);
        }

        $item = WishlistItem::create([
            'wishlist_id' => $wishlistId,
            'product_id' => $request->product_id,
            'note' => $request->note,
            'priority' => $request->priority ?? 0,
            'price_when_added' => $product->price,
        ]);

        return response()->json([
            'message' => 'Product added to wishlist',
            'item' => $item->load('product'),
        ], 201);
    }

    /**
     * Quick add to default wishlist
     */
    public function quickAdd(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = Auth::user();

        // Get or create default wishlist
        $wishlist = Wishlist::where('user_id', $user->id)
            ->where('is_default', true)
            ->first();

        if (!$wishlist) {
            $wishlist = Wishlist::create([
                'user_id' => $user->id,
                'name' => 'My Wishlist',
                'is_default' => true,
            ]);
        }

        $product = Product::findOrFail($request->product_id);

        // Check if already in wishlist
        $existing = WishlistItem::where('wishlist_id', $wishlist->id)
            ->where('product_id', $request->product_id)
            ->first();

        if ($existing) {
            return response()->json(['error' => 'Product already in wishlist'], 409);
        }

        $item = WishlistItem::create([
            'wishlist_id' => $wishlist->id,
            'product_id' => $request->product_id,
            'price_when_added' => $product->price,
        ]);

        return response()->json([
            'message' => 'Product added to wishlist',
            'item' => $item->load('product'),
        ], 201);
    }

    /**
     * Remove product from wishlist
     */
    public function removeItem($wishlistId, $itemId)
    {
        $wishlist = Wishlist::findOrFail($wishlistId);

        // Check ownership
        if ($wishlist->user_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $item = WishlistItem::where('wishlist_id', $wishlistId)
            ->where('id', $itemId)
            ->firstOrFail();

        $item->delete();

        return response()->json(['message' => 'Product removed from wishlist']);
    }

    /**
     * Update wishlist item
     */
    public function updateItem(Request $request, $wishlistId, $itemId)
    {
        $validator = Validator::make($request->all(), [
            'note' => 'nullable|string',
            'priority' => 'sometimes|integer|min:0|max:2',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $wishlist = Wishlist::findOrFail($wishlistId);

        // Check ownership
        if ($wishlist->user_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $item = WishlistItem::where('wishlist_id', $wishlistId)
            ->where('id', $itemId)
            ->firstOrFail();

        $item->update($request->only(['note', 'priority']));

        return response()->json([
            'message' => 'Wishlist item updated',
            'item' => $item,
        ]);
    }

    /**
     * Share wishlist
     */
    public function share(Request $request, $wishlistId)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'permission' => 'sometimes|in:view,edit',
            'expires_in_days' => 'nullable|integer|min:1|max:365',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $wishlist = Wishlist::findOrFail($wishlistId);

        // Check ownership
        if ($wishlist->user_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Generate share token if doesn't exist
        if (!$wishlist->share_token) {
            $wishlist->update(['share_token' => Str::random(32)]);
        }

        // Check if already shared with this email
        $existing = WishlistShare::where('wishlist_id', $wishlistId)
            ->where('email', $request->email)
            ->first();

        if ($existing) {
            return response()->json(['error' => 'Already shared with this email'], 409);
        }

        $expiresAt = null;
        if ($request->expires_in_days) {
            $expiresAt = now()->addDays($request->expires_in_days);
        }

        $share = WishlistShare::create([
            'wishlist_id' => $wishlistId,
            'email' => $request->email,
            'permission' => $request->permission ?? 'view',
            'expires_at' => $expiresAt,
        ]);

        // TODO: Send email notification

        return response()->json([
            'message' => 'Wishlist shared successfully',
            'share' => $share,
            'share_url' => $wishlist->share_url,
        ], 201);
    }

    /**
     * Get shared wishlist by token
     */
    public function getShared($token)
    {
        $wishlist = Wishlist::where('share_token', $token)
            ->whereIn('privacy', ['public', 'shared'])
            ->with(['items.product', 'user:id,name'])
            ->firstOrFail();

        return response()->json($wishlist);
    }

    /**
     * Check if product is in any wishlist
     */
    public function checkProduct($productId)
    {
        $wishlists = Wishlist::whereHas('items', function ($query) use ($productId) {
                $query->where('product_id', $productId);
            })
            ->where('user_id', Auth::id())
            ->get(['id', 'name']);

        return response()->json([
            'in_wishlist' => $wishlists->isNotEmpty(),
            'wishlists' => $wishlists,
        ]);
    }
}
