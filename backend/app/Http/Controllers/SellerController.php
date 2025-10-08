<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class SellerController extends Controller
{
    public function getListings(Request $request)
    {
        $user = $request->user();
        $listings = Product::where('seller_id', $user->id)->get();
        return response()->json($listings);
    }

    public function getAnalytics(Request $request)
    {
        $user = $request->user();
        $totalSales = Product::where('seller_id', $user->id)->sum('price');
        $totalListings = Product::where('seller_id', $user->id)->count();
        
        return response()->json([
            'total_sales' => $totalSales,
            'total_listings' => $totalListings,
            'active_listings' => Product::where('seller_id', $user->id)->where('stock', '>', 0)->count(),
        ]);
    }
}
