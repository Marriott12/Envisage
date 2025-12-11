<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RecentlyViewedController extends Controller
{
    public function index()
    {
        $viewed = DB::table('recently_viewed')
            ->join('products', 'recently_viewed.product_id', '=', 'products.id')
            ->where('recently_viewed.user_id', auth()->id())
            ->orderBy('recently_viewed.viewed_at', 'desc')
            ->limit(20)
            ->select('products.*', 'recently_viewed.viewed_at')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $viewed,
        ]);
    }

    public function track(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        DB::table('recently_viewed')->insert([
            'user_id' => auth()->id(),
            'product_id' => $request->product_id,
            'viewed_at' => now(),
        ]);

        // Keep only last 50 items
        $keepIds = DB::table('recently_viewed')
            ->where('user_id', auth()->id())
            ->orderBy('viewed_at', 'desc')
            ->limit(50)
            ->pluck('id');

        DB::table('recently_viewed')
            ->where('user_id', auth()->id())
            ->whereNotIn('id', $keepIds)
            ->delete();

        return response()->json(['success' => true]);
    }

    public function remove($id)
    {
        DB::table('recently_viewed')
            ->where('user_id', auth()->id())
            ->where('product_id', $id)
            ->delete();

        return response()->json(['success' => true]);
    }
}
