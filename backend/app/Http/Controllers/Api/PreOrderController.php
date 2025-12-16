<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PreOrder;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PreOrderController extends Controller
{
    /**
     * Create a new pre-order
     */
    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'payment_option' => 'required|in:full,deposit,no_charge',
        ]);

        $product = Product::findOrFail($request->product_id);

        // Verify product is available for pre-order
        if (!$product->is_preorder) {
            return response()->json([
                'success' => false,
                'message' => 'Product is not available for pre-order',
            ], 400);
        }

        // Check pre-order limit
        if ($product->preorder_limit) {
            $existingPreOrders = PreOrder::where('product_id', $product->id)
                ->where('status', 'reserved')
                ->sum('quantity');

            if ($existingPreOrders + $request->quantity > $product->preorder_limit) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pre-order limit exceeded',
                ], 400);
            }
        }

        $totalAmount = $product->price * $request->quantity;
        $depositAmount = 0;
        $depositPaid = false;

        // Calculate deposit based on payment option
        if ($request->payment_option === 'deposit') {
            $depositAmount = $totalAmount * 0.20; // 20% deposit
            $depositPaid = true;
        } elseif ($request->payment_option === 'full') {
            $depositAmount = $totalAmount;
            $depositPaid = true;
        }

        DB::beginTransaction();
        try {
            $preOrder = PreOrder::create([
                'product_id' => $product->id,
                'user_id' => Auth::id(),
                'quantity' => $request->quantity,
                'price_per_unit' => $product->price,
                'total_amount' => $totalAmount,
                'deposit_paid' => $depositPaid,
                'deposit_amount' => $depositAmount,
                'status' => 'reserved',
                'expected_ship_date' => $product->expected_ship_date,
                'notes' => $request->notes,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pre-order created successfully',
                'pre_order' => $preOrder->load('product'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create pre-order: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get user's pre-orders
     */
    public function getUserPreOrders()
    {
        $preOrders = PreOrder::with('product')
            ->where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'pre_orders' => $preOrders,
        ]);
    }

    /**
     * Get a specific pre-order
     */
    public function show($id)
    {
        $preOrder = PreOrder::with(['product', 'notifications'])
            ->where('user_id', Auth::id())
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'pre_order' => $preOrder,
        ]);
    }

    /**
     * Cancel a pre-order
     */
    public function cancel($id)
    {
        $preOrder = PreOrder::where('user_id', Auth::id())->findOrFail($id);

        if (!$preOrder->isActive()) {
            return response()->json([
                'success' => false,
                'message' => 'Pre-order cannot be cancelled',
            ], 400);
        }

        $preOrder->cancel();

        return response()->json([
            'success' => true,
            'message' => 'Pre-order cancelled successfully',
        ]);
    }

    /**
     * Get pre-orders for a specific product (seller/admin)
     */
    public function getProductPreOrders($productId)
    {
        $product = Product::findOrFail($productId);

        // Verify user is seller or admin
        if ($product->seller_id !== Auth::id() && !Auth::user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $preOrders = PreOrder::with('user')
            ->where('product_id', $productId)
            ->orderBy('created_at', 'desc')
            ->get();

        $summary = [
            'total_quantity' => $preOrders->where('status', 'reserved')->sum('quantity'),
            'total_revenue' => $preOrders->where('status', 'reserved')->sum('deposit_amount'),
            'active_count' => $preOrders->where('status', 'reserved')->count(),
            'cancelled_count' => $preOrders->where('status', 'cancelled')->count(),
        ];

        return response()->json([
            'success' => true,
            'pre_orders' => $preOrders,
            'summary' => $summary,
        ]);
    }
}
