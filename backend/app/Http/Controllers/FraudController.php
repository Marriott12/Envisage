<?php

namespace App\Http\Controllers;

use App\Services\AdvancedFraudDetectionService;
use App\Models\FraudAlert;
use Illuminate\Http\Request;

class FraudController extends Controller
{
    protected $fraudService;

    public function __construct(AdvancedFraudDetectionService $fraudService)
    {
        $this->fraudService = $fraudService;
    }

    /**
     * Check transaction for fraud
     */
    public function check(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'amount' => 'required|numeric|min:0',
            'payment_method' => 'required|string',
            'items' => 'array',
            'shipping_address' => 'array',
            'billing_address' => 'array',
        ]);

        $transactionData = $request->only([
            'user_id', 'amount', 'payment_method', 'items',
            'shipping_address', 'billing_address'
        ]);

        // Add request metadata
        $transactionData['ip_address'] = $request->ip();
        $transactionData['user_agent'] = $request->userAgent();
        $transactionData['device_fingerprint'] = $request->header('X-Device-Fingerprint');

        $result = $this->fraudService->checkTransaction($transactionData);

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    /**
     * Get fraud alerts
     */
    public function alerts(Request $request)
    {
        $query = FraudAlert::with(['user', 'order'])
            ->orderBy('created_at', 'desc');

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        // Filter by risk level
        if ($request->has('risk_level')) {
            $query->where('risk_level', $request->input('risk_level'));
        }

        // Filter by date range
        if ($request->has('from_date')) {
            $query->where('created_at', '>=', $request->input('from_date'));
        }

        if ($request->has('to_date')) {
            $query->where('created_at', '<=', $request->input('to_date'));
        }

        $perPage = $request->input('per_page', 20);
        $alerts = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $alerts,
        ]);
    }

    /**
     * Review fraud alert
     */
    public function review(Request $request, $alertId)
    {
        $request->validate([
            'action' => 'required|in:approve,block',
            'notes' => 'nullable|string',
        ]);

        $alert = FraudAlert::findOrFail($alertId);

        $alert->update([
            'status' => $request->input('action') === 'approve' ? 'approved' : 'blocked',
            'reviewed_at' => now(),
            'reviewed_by' => $request->user()->id,
            'action_taken' => $request->input('notes'),
        ]);

        // If blocking, also block the associated order
        if ($request->input('action') === 'block' && $alert->order_id) {
            $alert->order->update(['status' => 'cancelled']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Alert reviewed successfully',
            'data' => $alert,
        ]);
    }

    /**
     * Get fraud statistics
     */
    public function statistics(Request $request)
    {
        $fromDate = $request->input('from_date', now()->subDays(30));
        $toDate = $request->input('to_date', now());

        $stats = [
            'total_checks' => FraudAlert::whereBetween('created_at', [$fromDate, $toDate])->count(),
            'by_risk_level' => FraudAlert::whereBetween('created_at', [$fromDate, $toDate])
                ->selectRaw('risk_level, COUNT(*) as count')
                ->groupBy('risk_level')
                ->pluck('count', 'risk_level'),
            'by_status' => FraudAlert::whereBetween('created_at', [$fromDate, $toDate])
                ->selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status'),
            'average_score' => FraudAlert::whereBetween('created_at', [$fromDate, $toDate])
                ->avg('risk_score'),
            'blocked_amount' => FraudAlert::where('status', 'blocked')
                ->whereBetween('created_at', [$fromDate, $toDate])
                ->join('orders', 'fraud_alerts.order_id', '=', 'orders.id')
                ->sum('orders.total'),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }
}
