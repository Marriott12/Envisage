<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class SellerController extends Controller
{
    public function getListings(Request $request)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized'
            ], 401);
        }
        
        $listings = Product::where('seller_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();
        
        return response()->json([
            'status' => 'success',
            'message' => 'Listings retrieved successfully',
            'data' => $listings
        ]);
    }

    public function getAnalytics(Request $request)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized'
            ], 401);
        }
        
        $totalSales = Product::where('seller_id', $user->id)->sum('price');
        $totalListings = Product::where('seller_id', $user->id)->count();
        
        return response()->json([
            'status' => 'success',
            'data' => [
                'total_sales' => $totalSales,
                'total_listings' => $totalListings,
                'active_listings' => Product::where('seller_id', $user->id)->where('stock', '>', 0)->count(),
            ]
        ]);
    }
}
