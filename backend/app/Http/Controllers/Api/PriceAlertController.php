<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PriceDropAlert;
use App\Models\Product;
use Illuminate\Http\Request;

class PriceAlertController extends Controller
{
    // Get user's price alerts
    public function index(Request $request)
    {
        $alerts = PriceDropAlert::where('user_id', $request->user()->id)
            ->with(['product:id,name,price,image'])
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 20));

        return response()->json($alerts);
    }

    // Create price alert
    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'target_price' => 'required|numeric|min:0',
        ]);

        $product = Product::findOrFail($request->product_id);

        // Check if alert already exists
        $existing = PriceDropAlert::where('user_id', $request->user()->id)
            ->where('product_id', $request->product_id)
            ->where('is_active', true)
            ->first();

        if ($existing) {
            return response()->json([
                'error' => 'You already have an active alert for this product',
            ], 422);
        }

        // Check if target price is valid
        if ($request->target_price >= $product->price) {
            return response()->json([
                'error' => 'Target price must be lower than current price',
            ], 422);
        }

        $alert = PriceDropAlert::create([
            'user_id' => $request->user()->id,
            'product_id' => $request->product_id,
            'target_price' => $request->target_price,
            'original_price' => $product->price,
            'is_active' => true,
        ]);

        return response()->json([
            'message' => 'Price alert created successfully',
            'alert' => $alert->load('product:id,name,price,image'),
        ], 201);
    }

    // Update price alert
    public function update(Request $request, $alertId)
    {
        $request->validate([
            'target_price' => 'required|numeric|min:0',
        ]);

        $alert = PriceDropAlert::where('id', $alertId)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $product = Product::findOrFail($alert->product_id);

        if ($request->target_price >= $product->price) {
            return response()->json([
                'error' => 'Target price must be lower than current price',
            ], 422);
        }

        $alert->update([
            'target_price' => $request->target_price,
            'is_active' => true,
            'notified' => false,
        ]);

        return response()->json([
            'message' => 'Price alert updated successfully',
            'alert' => $alert->load('product:id,name,price,image'),
        ]);
    }

    // Delete price alert
    public function destroy(Request $request, $alertId)
    {
        $alert = PriceDropAlert::where('id', $alertId)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $alert->delete();

        return response()->json(['message' => 'Price alert deleted successfully']);
    }

    // Check prices (cron job endpoint)
    public function checkPrices()
    {
        $alerts = PriceDropAlert::where('is_active', true)
            ->where('notified', false)
            ->with(['product', 'user'])
            ->get();

        $notifiedCount = 0;

        foreach ($alerts as $alert) {
            if ($alert->shouldNotify($alert->product->price)) {
                // TODO: Send email notification
                // Mail::to($alert->user->email)->send(new PriceDropNotification($alert));
                
                $alert->markAsNotified();
                $notifiedCount++;
            }
        }

        return response()->json([
            'message' => 'Price check completed',
            'notified' => $notifiedCount,
        ]);
    }
}
