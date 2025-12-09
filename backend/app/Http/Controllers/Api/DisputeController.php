<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderDispute;
use App\Models\ReturnRequest;
use App\Models\Refund;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class DisputeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function createDispute(Request $request, $orderId)
    {
        $request->validate([
            'type' => 'required|in:return,refund,complaint,quality_issue,not_received',
            'reason' => 'required|string|max:1000',
            'evidence' => 'nullable|array|max:5',
            'evidence.*' => 'file|max:10240|mimes:jpg,jpeg,png,pdf',
        ]);

        $order = Order::findOrFail($orderId);
        $userId = Auth::id();

        if ($order->buyer_id !== $userId) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $evidencePaths = [];
        if ($request->hasFile('evidence')) {
            foreach ($request->file('evidence') as $file) {
                $path = $file->store('disputes/evidence', 'public');
                $evidencePaths[] = $path;
            }
        }

        $dispute = OrderDispute::create([
            'order_id' => $order->id,
            'user_id' => $userId,
            'seller_id' => $order->seller_id,
            'type' => $request->type,
            'reason' => $request->reason,
            'evidence' => $evidencePaths,
            'status' => 'pending',
        ]);

        // TODO: Notify seller and admin
        // TODO: Send email notification

        return response()->json(['dispute' => $dispute], 201);
    }

    public function updateDispute(Request $request, $disputeId)
    {
        $request->validate([
            'status' => 'required|in:pending,approved,rejected,resolved,escalated',
            'admin_notes' => 'nullable|string|max:1000',
        ]);

        $dispute = OrderDispute::findOrFail($disputeId);

        // Only admin can update dispute status
        if (!Auth::user()->hasRole('admin')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $dispute->update([
            'status' => $request->status,
            'admin_notes' => $request->admin_notes,
            'resolved_at' => in_array($request->status, ['approved', 'rejected', 'resolved']) ? now() : null,
            'resolver_id' => Auth::id(),
        ]);

        // TODO: Notify buyer and seller
        // TODO: Send email notification

        return response()->json(['dispute' => $dispute]);
    }

    public function createReturn(Request $request, $orderId)
    {
        $request->validate([
            'reason' => 'required|in:defective,wrong_item,not_as_described,changed_mind,damaged',
            'description' => 'required|string|max:1000',
            'images' => 'nullable|array|max:5',
            'images.*' => 'image|max:5120',
        ]);

        $order = Order::findOrFail($orderId);
        $userId = Auth::id();

        if ($order->buyer_id !== $userId) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $imagePaths = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image->store('returns/images', 'public');
                $imagePaths[] = $path;
            }
        }

        $returnRequest = ReturnRequest::create([
            'order_id' => $order->id,
            'user_id' => $userId,
            'seller_id' => $order->seller_id,
            'reason' => $request->reason,
            'description' => $request->description,
            'images' => $imagePaths,
            'status' => 'pending',
        ]);

        // TODO: Notify seller
        // TODO: Send email notification

        return response()->json(['return_request' => $returnRequest], 201);
    }

    public function approveReturn(Request $request, $returnId)
    {
        $request->validate([
            'approved' => 'required|boolean',
            'rejection_reason' => 'nullable|string|max:500',
        ]);

        $returnRequest = ReturnRequest::findOrFail($returnId);

        // Only seller can approve/reject
        if ($returnRequest->seller_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($request->approved) {
            $returnRequest->update([
                'status' => 'approved',
                'approved_at' => now(),
            ]);
        } else {
            $returnRequest->update([
                'status' => 'rejected',
                'rejection_reason' => $request->rejection_reason,
            ]);
        }

        // TODO: Notify buyer
        // TODO: Send email notification

        return response()->json(['return_request' => $returnRequest]);
    }

    public function updateReturnTracking(Request $request, $returnId)
    {
        $request->validate([
            'tracking_number' => 'required|string|max:100',
            'courier' => 'nullable|string|max:100',
        ]);

        $returnRequest = ReturnRequest::findOrFail($returnId);

        // Only buyer can update tracking
        if ($returnRequest->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $returnRequest->update([
            'tracking_number' => $request->tracking_number,
            'courier' => $request->courier,
            'status' => 'shipped',
        ]);

        // TODO: Notify seller

        return response()->json(['return_request' => $returnRequest]);
    }

    public function confirmReturn($returnId)
    {
        $returnRequest = ReturnRequest::findOrFail($returnId);

        // Only seller can confirm receipt
        if ($returnRequest->seller_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $returnRequest->update([
            'status' => 'received',
            'received_at' => now(),
        ]);

        // Process refund
        $refundAmount = $returnRequest->order->total_amount;
        
        $refund = Refund::create([
            'return_request_id' => $returnRequest->id,
            'order_id' => $returnRequest->order_id,
            'amount' => $refundAmount,
            'status' => 'pending',
        ]);

        // TODO: Process actual refund via payment gateway
        // TODO: Notify buyer

        return response()->json([
            'return_request' => $returnRequest,
            'refund' => $refund,
        ]);
    }

    public function listDisputes(Request $request)
    {
        $userId = Auth::id();
        
        $query = OrderDispute::query();
        
        if (!Auth::user()->hasRole('admin')) {
            $query->where(function($q) use ($userId) {
                $q->where('user_id', $userId)
                  ->orWhere('seller_id', $userId);
            });
        }

        $disputes = $query->with(['order', 'user', 'seller'])
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json($disputes);
    }

    public function listReturns(Request $request)
    {
        $userId = Auth::id();
        
        $query = ReturnRequest::query();
        
        if (!Auth::user()->hasRole('admin')) {
            $query->where(function($q) use ($userId) {
                $q->where('user_id', $userId)
                  ->orWhere('seller_id', $userId);
            });
        }

        $returns = $query->with(['order', 'user', 'seller', 'refund'])
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json($returns);
    }
}
