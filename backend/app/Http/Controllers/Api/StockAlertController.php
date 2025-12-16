<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\StockAlert;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class StockAlertController extends Controller
{
    public function subscribe(Request $request, $productId)
    {
        $request->validate([
            'email' => 'nullable|email',
            'phone' => 'nullable|string',
        ]);

        $product = Product::findOrFail($productId);

        $alert = StockAlert::create([
            'user_id' => Auth::id(),
            'product_id' => $productId,
            'email' => $request->email ?? Auth::user()->email,
            'phone' => $request->phone,
            'status' => 'active',
        ]);

        return response()->json([
            'message' => 'You will be notified when this product is back in stock',
            'alert' => $alert,
        ], 201);
    }

    public function unsubscribe($id)
    {
        $alert = StockAlert::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $alert->update(['status' => 'cancelled']);

        return response()->json([
            'message' => 'Stock alert cancelled',
        ]);
    }

    public function myAlerts()
    {
        $alerts = StockAlert::with('product')
            ->where('user_id', Auth::id())
            ->where('status', 'active')
            ->latest()
            ->paginate(20);

        return response()->json($alerts);
    }

    public function checkAlert($productId)
    {
        $alert = StockAlert::where('product_id', $productId)
            ->where('user_id', Auth::id())
            ->where('status', 'active')
            ->first();

        return response()->json([
            'subscribed' => $alert !== null,
            'alert' => $alert,
        ]);
    }
}
