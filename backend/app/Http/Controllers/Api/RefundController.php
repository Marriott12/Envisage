<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Refund;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RefundController extends Controller
{
    /**
     * Get all refund requests
     */
    public function index(Request $request)
    {
        $query = Refund::with(['user', 'order.items.product']);

        // Filter by status
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Search
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->whereHas('user', function($userQuery) use ($search) {
                    $userQuery->where('name', 'like', "%{$search}%")
                             ->orWhere('email', 'like', "%{$search}%");
                })
                ->orWhereHas('order', function($orderQuery) use ($search) {
                    $orderQuery->where('order_number', 'like', "%{$search}%");
                });
            });
        }

        $refunds = $query->orderBy('requested_at', 'desc')
                         ->paginate($request->per_page ?? 15);

        // Transform data for frontend
        $refunds->getCollection()->transform(function($refund) {
            return [
                'id' => $refund->id,
                'order_id' => $refund->order->order_number ?? 'N/A',
                'customer_name' => $refund->user->name ?? 'Unknown',
                'customer_email' => $refund->user->email ?? '',
                'customer_avatar' => $refund->user->avatar ?? "https://ui-avatars.com/api/?name=" . urlencode($refund->user->name ?? 'User'),
                'amount' => (float) $refund->amount,
                'reason' => $refund->reason,
                'description' => $refund->description,
                'status' => $refund->status,
                'payment_method' => $refund->payment_method,
                'requested_at' => $refund->requested_at->format('Y-m-d H:i:s'),
                'processed_at' => $refund->processed_at ? $refund->processed_at->format('Y-m-d H:i:s') : null,
                'admin_notes' => $refund->admin_notes,
                'attachments' => $refund->attachments ?? [],
                'items' => $refund->order->items->map(fn($item) => $item->product->name ?? 'Product')->toArray() ?? [],
            ];
        });

        // Calculate stats
        $stats = [
            'total' => Refund::count(),
            'pending' => Refund::where('status', 'pending')->count(),
            'approved' => Refund::where('status', 'approved')->count(),
            'completed' => Refund::where('status', 'completed')->count(),
            'rejected' => Refund::where('status', 'rejected')->count(),
            'total_amount' => Refund::whereIn('status', ['approved', 'completed'])->sum('amount'),
        ];

        return response()->json([
            'success' => true,
            'data' => $refunds->items(),
            'stats' => $stats,
            'pagination' => [
                'total' => $refunds->total(),
                'per_page' => $refunds->perPage(),
                'current_page' => $refunds->currentPage(),
                'last_page' => $refunds->lastPage(),
            ],
        ]);
    }

    /**
     * Process refund (approve/reject)
     */
    public function process(Request $request, $id)
    {
        $request->validate([
            'action' => 'required|in:approve,reject',
            'admin_notes' => 'nullable|string',
        ]);

        $refund = Refund::with(['order', 'user'])->findOrFail($id);

        $status = $request->action === 'approve' ? 'approved' : 'rejected';

        $refund->update([
            'status' => $status,
            'admin_notes' => $request->admin_notes,
            'processed_at' => now(),
            'processed_by' => auth()->id(),
        ]);

        // If approved, process the actual refund
        if ($status === 'approved') {
            // Here you would integrate with payment gateway (Stripe, PayPal, etc.)
            // For now, just update status to processing
            $refund->update([
                'status' => 'processing',
                'refund_reference' => 'REF-' . strtoupper(uniqid()),
            ]);

            // TODO: Send email notification to customer
        }

        return response()->json([
            'success' => true,
            'message' => 'Refund ' . $status . ' successfully',
            'data' => [
                'id' => $refund->id,
                'status' => $refund->status,
                'admin_notes' => $refund->admin_notes,
                'processed_at' => $refund->processed_at->format('Y-m-d H:i:s'),
                'refund_reference' => $refund->refund_reference,
            ],
        ]);
    }

    /**
     * Create a refund request
     */
    public function store(Request $request)
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id',
            'amount' => 'required|numeric|min:0.01',
            'reason' => 'required|string',
            'description' => 'nullable|string',
            'payment_method' => 'nullable|string',
            'attachments' => 'nullable|array',
        ]);

        $order = Order::findOrFail($request->order_id);

        // Validate refund amount doesn't exceed order total
        if ($request->amount > $order->total_amount) {
            return response()->json([
                'success' => false,
                'message' => 'Refund amount cannot exceed order total',
            ], 400);
        }

        $refund = Refund::create([
            'order_id' => $request->order_id,
            'user_id' => $order->user_id,
            'amount' => $request->amount,
            'reason' => $request->reason,
            'description' => $request->description,
            'payment_method' => $request->payment_method ?? $order->payment_method,
            'attachments' => $request->attachments,
            'status' => 'pending',
            'requested_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Refund request submitted successfully',
            'data' => $refund,
        ], 201);
    }
}
