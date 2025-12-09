<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RefundController extends Controller
{
    /**
     * Get all refund requests
     */
    public function index(Request $request)
    {
        // Mock refund data - in production, use actual refunds table
        $refunds = collect([
            [
                'id' => 1,
                'order_id' => 'ORD-2024-001',
                'customer_name' => 'John Doe',
                'customer_email' => 'john@example.com',
                'customer_avatar' => 'https://ui-avatars.com/api/?name=John+Doe',
                'amount' => 149.99,
                'reason' => 'Product damaged on arrival',
                'status' => 'pending',
                'payment_method' => 'Credit Card',
                'requested_at' => '2024-12-01 10:30:00',
                'items' => ['Wireless Headphones'],
            ],
            [
                'id' => 2,
                'order_id' => 'ORD-2024-002',
                'customer_name' => 'Jane Smith',
                'customer_email' => 'jane@example.com',
                'customer_avatar' => 'https://ui-avatars.com/api/?name=Jane+Smith',
                'amount' => 89.50,
                'reason' => 'Wrong item received',
                'status' => 'approved',
                'payment_method' => 'PayPal',
                'requested_at' => '2024-11-30 14:20:00',
                'items' => ['Smart Watch Band'],
            ],
        ]);

        // Filter by status
        if ($request->has('status')) {
            $refunds = $refunds->where('status', $request->input('status'));
        }

        // Search
        if ($request->has('search')) {
            $search = strtolower($request->input('search'));
            $refunds = $refunds->filter(function($refund) use ($search) {
                return str_contains(strtolower($refund['customer_name']), $search) ||
                       str_contains(strtolower($refund['customer_email']), $search) ||
                       str_contains(strtolower($refund['order_id']), $search);
            });
        }

        return response()->json([
            'success' => true,
            'data' => $refunds->values(),
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

        $action = $request->input('action');
        $notes = $request->input('admin_notes');

        // In production, update refund in database
        $status = $action === 'approve' ? 'approved' : 'rejected';

        return response()->json([
            'success' => true,
            'message' => 'Refund ' . $status . ' successfully',
            'data' => [
                'id' => $id,
                'status' => $status,
                'admin_notes' => $notes,
                'processed_at' => now()->format('Y-m-d H:i:s'),
            ],
        ]);
    }
}
