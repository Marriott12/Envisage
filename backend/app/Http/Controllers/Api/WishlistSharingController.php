<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Wishlist;
use App\Models\SharedWishlist;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class WishlistSharingController extends Controller
{
    /**
     * Share wishlist
     */
    public function share(Request $request)
    {
        $request->validate([
            'wishlist_id' => 'required|integer',
            'privacy' => 'required|in:public,private,friends',
            'allow_comments' => 'boolean',
        ]);

        $user = $request->user();
        
        $wishlist = Wishlist::where('id', $request->wishlist_id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        // Generate unique share token
        $token = Str::random(32);

        $shared = SharedWishlist::create([
            'wishlist_id' => $wishlist->id,
            'user_id' => $user->id,
            'share_token' => $token,
            'privacy' => $request->privacy,
            'allow_comments' => $request->allow_comments ?? true,
            'expires_at' => $request->expires_at ?? null,
        ]);

        return response()->json([
            'message' => 'Wishlist shared successfully',
            'share_url' => url("/shared/wishlist/{$token}"),
            'share_token' => $token
        ]);
    }

    /**
     * Get shared wishlist
     */
    public function getShared($token)
    {
        $shared = SharedWishlist::where('share_token', $token)
            ->with(['wishlist.items.product', 'user'])
            ->firstOrFail();

        // Check expiration
        if ($shared->expires_at && now()->greaterThan($shared->expires_at)) {
            return response()->json([
                'message' => 'This shared wishlist has expired'
            ], 410);
        }

        $shared->increment('views_count');

        return response()->json([
            'wishlist' => $shared->wishlist,
            'owner' => [
                'name' => $shared->user->name,
                'avatar' => $shared->user->avatar,
            ],
            'allow_comments' => $shared->allow_comments,
            'created_at' => $shared->created_at,
        ]);
    }

    /**
     * Revoke share
     */
    public function revoke(Request $request, $token)
    {
        $user = $request->user();

        $shared = SharedWishlist::where('share_token', $token)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $shared->delete();

        return response()->json([
            'message' => 'Wishlist share revoked successfully'
        ]);
    }
}
