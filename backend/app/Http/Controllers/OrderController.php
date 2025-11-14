<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\CartItem;
use App\Models\Product;
use App\Mail\OrderConfirmation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    /**
     * Get user's orders with optional filters
     */
    public function index(Request $request)
    {
        $query = Order::where('user_id', auth()->id())
            ->with('items.product:id,title,images', 'items.seller:id,name');
        
        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        // Filter by payment status
        if ($request->has('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }
        
        // Search by order number
        if ($request->has('search')) {
            $query->where('order_number', 'like', '%' . $request->search . '%');
        }
        
        // Sort by date (newest first by default)
        $query->orderBy('created_at', 'desc');
        
        $orders = $query->paginate($request->per_page ?? 15);
        
        return response()->json($orders);
    }
    
    /**
     * Get single order details
     */
    public function show($id)
    {
        $order = Order::where('user_id', auth()->id())
            ->with(['items.product:id,title,images', 'items.seller:id,name,email'])
            ->findOrFail($id);
        
        return response()->json($order);
    }
    
    /**
     * Checkout - Create order from cart
     */
    public function checkout(Request $request)
    {
        $validated = $request->validate([
            'payment_method' => 'required|string',
            'shipping_address' => 'required|array',
            'shipping_address.name' => 'required|string',
            'shipping_address.phone' => 'required|string',
            'shipping_address.address' => 'required|string',
            'shipping_address.city' => 'required|string',
            'shipping_address.country' => 'required|string',
            'notes' => 'nullable|string|max:500',
        ]);
        
        $userId = auth()->id();
        
        // Get cart items
        $cartItems = CartItem::where('user_id', $userId)
            ->with('product')
            ->get();
        
        if ($cartItems->isEmpty()) {
            return response()->json([
                'message' => 'Your cart is empty'
            ], 400);
        }
        
        DB::beginTransaction();
        
        try {
            // Calculate totals
            $subtotal = 0;
            foreach ($cartItems as $item) {
                if (!$item->product) {
                    throw new \Exception('Product not found in cart');
                }
                
                // Check stock availability
                if ($item->product->stock < $item->quantity) {
                    throw new \Exception("Insufficient stock for {$item->product->title}");
                }
                
                $subtotal += $item->product->price * $item->quantity;
            }
            
            $shippingFee = 50.00; // Fixed shipping fee (can be dynamic)
            $total = $subtotal + $shippingFee;
            
            // Create order
            $order = Order::create([
                'order_number' => 'ORD-' . strtoupper(Str::random(10)),
                'user_id' => $userId,
                'subtotal' => $subtotal,
                'shipping_fee' => $shippingFee,
                'total' => $total,
                'payment_method' => $validated['payment_method'],
                'payment_status' => 'pending',
                'status' => 'pending',
                'shipping_address' => $validated['shipping_address'],
                'notes' => $validated['notes'] ?? null,
            ]);
            
            // Create order items and update product stock
            foreach ($cartItems as $item) {
                $product = $item->product;
                
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'seller_id' => $product->seller_id,
                    'product_title' => $product->title, // Snapshot
                    'quantity' => $item->quantity,
                    'price' => $product->price, // Snapshot
                    'subtotal' => $product->price * $item->quantity,
                ]);
                
                // Decrement stock and increment sold count
                $product->decrement('stock', $item->quantity);
                $product->increment('sold', $item->quantity);
                
                // Auto-update product status if out of stock
                if ($product->stock <= 0) {
                    $product->update(['status' => 'out_of_stock']);
                }
            }
            
            // Clear cart
            CartItem::where('user_id', $userId)->delete();
            
            DB::commit();
            
            // Load relationships for response
            $order->load('items.product');
            
            // Send order confirmation email
            try {
                Mail::to(auth()->user()->email)->send(new OrderConfirmation($order));
            } catch (\Exception $e) {
                Log::error('Failed to send order confirmation email: ' . $e->getMessage());
            }
            
            return response()->json([
                'message' => 'Order placed successfully',
                'order' => $order
            ], 201);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'message' => 'Checkout failed: ' . $e->getMessage()
            ], 400);
        }
    }
    
    /**
     * Cancel order
     */
    public function cancel($id)
    {
        $order = Order::where('user_id', auth()->id())->findOrFail($id);
        
        // Only pending or processing orders can be cancelled
        if (!in_array($order->status, ['pending', 'processing'])) {
            return response()->json([
                'message' => 'This order cannot be cancelled'
            ], 400);
        }
        
        DB::beginTransaction();
        
        try {
            // Restore product stock
            foreach ($order->items as $item) {
                if ($item->product) {
                    $item->product->increment('stock', $item->quantity);
                    $item->product->decrement('sold', $item->quantity);
                    
                    // Update product status if it was out of stock
                    if ($item->product->status === 'out_of_stock' && $item->product->stock > 0) {
                        $item->product->update(['status' => 'active']);
                    }
                }
            }
            
            // Update order status
            $order->update(['status' => 'cancelled']);
            
            DB::commit();
            
            return response()->json([
                'message' => 'Order cancelled successfully',
                'order' => $order
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'message' => 'Failed to cancel order: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Update order status (Seller/Admin only)
     */
    public function updateStatus(Request $request, $id)
    {
        $order = Order::findOrFail($id);

        // Check if user is seller of this order's items or admin
        $isSeller = $order->items()->whereHas('product', function($q) {
            $q->where('seller_id', auth()->id());
        })->exists();

        if (!$isSeller && auth()->user()->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'status' => 'required|in:pending,processing,shipped,delivered,cancelled',
            'tracking_number' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $order->update([
            'status' => $validated['status'],
            'tracking_number' => $validated['tracking_number'] ?? $order->tracking_number,
            'notes' => $validated['notes'] ?? $order->notes,
        ]);

        // Send status update email
        try {
            Mail::to($order->user->email)->send(new \App\Mail\OrderStatusUpdate($order));
        } catch (\Exception $e) {
            Log::error('Failed to send order status update email: ' . $e->getMessage());
        }

        return response()->json([
            'message' => 'Order status updated successfully',
            'order' => $order
        ]);
    }

    /**
     * Get seller's orders
     */
    public function sellerOrders(Request $request)
    {
        // Get orders that contain products from this seller
        $query = Order::whereHas('items.product', function($q) {
            $q->where('seller_id', auth()->id());
        })->with(['items' => function($q) {
            $q->whereHas('product', function($q2) {
                $q2->where('seller_id', auth()->id());
            })->with('product:id,title,images,price');
        }, 'user:id,name,email']);

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by payment status
        if ($request->has('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        $query->orderBy('created_at', 'desc');
        
        return response()->json($query->paginate($request->per_page ?? 20));
    }

    /**
     * Get order statistics (Seller/Admin)
     */
    public function statistics(Request $request)
    {
        $query = Order::query();

        // Filter by seller if not admin
        if (auth()->user()->role !== 'admin') {
            $query->whereHas('items.product', function($q) {
                $q->where('seller_id', auth()->id());
            });
        }

        $stats = [
            'total_orders' => $query->count(),
            'pending_orders' => (clone $query)->where('status', 'pending')->count(),
            'processing_orders' => (clone $query)->where('status', 'processing')->count(),
            'shipped_orders' => (clone $query)->where('status', 'shipped')->count(),
            'delivered_orders' => (clone $query)->where('status', 'delivered')->count(),
            'cancelled_orders' => (clone $query)->where('status', 'cancelled')->count(),
            'total_revenue' => (clone $query)->where('payment_status', 'paid')->sum('total_amount'),
            'pending_payment' => (clone $query)->where('payment_status', 'pending')->sum('total_amount'),
        ];

        return response()->json($stats);
    }
}
