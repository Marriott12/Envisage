<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\FraudRule;
use App\Models\FraudScore;
use App\Models\FraudAttempt;
use App\Models\Blacklist;
use App\Models\VelocityTracking;
use App\Services\FraudDetectionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FraudDetectionController extends Controller
{
    protected $fraudService;

    public function __construct(FraudDetectionService $fraudService)
    {
        $this->fraudService = $fraudService;
    }

    /**
     * Analyze order for fraud
     * POST /api/fraud/analyze/{orderId}
     */
    public function analyzeOrder($orderId)
    {
        $order = Order::findOrFail($orderId);
        
        $fraudScore = $this->fraudService->analyzeOrder($order);

        return response()->json([
            'success' => true,
            'fraud_score' => $fraudScore,
            'analysis' => $fraudScore->getAnalysisSummary()
        ]);
    }

    /**
     * Get fraud score for order
     * GET /api/fraud/score/{orderId}
     */
    public function getScore($orderId)
    {
        $fraudScore = FraudScore::where('order_id', $orderId)
            ->with(['order', 'user'])
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'fraud_score' => $fraudScore,
            'analysis' => $fraudScore->getAnalysisSummary()
        ]);
    }

    /**
     * Get pending fraud reviews
     * GET /api/fraud/pending-reviews
     */
    public function getPendingReviews(Request $request)
    {
        $perPage = $request->get('per_page', 20);
        
        $scores = FraudScore::whereIn('status', ['pending', 'under_review'])
            ->with(['order', 'user'])
            ->orderBy('risk_level', 'desc')
            ->orderBy('created_at', 'asc')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'pending_reviews' => $scores
        ]);
    }

    /**
     * Approve fraud score
     * POST /api/fraud/approve/{scoreId}
     */
    public function approve($scoreId, Request $request)
    {
        $fraudScore = FraudScore::findOrFail($scoreId);
        
        $validator = Validator::make($request->all(), [
            'notes' => 'nullable|string|max:1000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $fraudScore->approve(auth()->id(), $request->notes);

        return response()->json([
            'success' => true,
            'message' => 'Order approved',
            'fraud_score' => $fraudScore->fresh()
        ]);
    }

    /**
     * Reject fraud score
     * POST /api/fraud/reject/{scoreId}
     */
    public function reject($scoreId, Request $request)
    {
        $fraudScore = FraudScore::findOrFail($scoreId);
        
        $validator = Validator::make($request->all(), [
            'notes' => 'required|string|max:1000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $fraudScore->reject(auth()->id(), $request->notes);

        return response()->json([
            'success' => true,
            'message' => 'Order rejected',
            'fraud_score' => $fraudScore->fresh()
        ]);
    }

    /**
     * Mark as false positive
     * POST /api/fraud/false-positive/{scoreId}
     */
    public function markFalsePositive($scoreId, Request $request)
    {
        $fraudScore = FraudScore::findOrFail($scoreId);
        
        $validator = Validator::make($request->all(), [
            'notes' => 'nullable|string|max:1000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $fraudScore->markAsFalsePositive(auth()->id(), $request->notes);

        return response()->json([
            'success' => true,
            'message' => 'Marked as false positive',
            'fraud_score' => $fraudScore->fresh()
        ]);
    }

    /**
     * List fraud rules
     * GET /api/fraud/rules
     */
    public function listRules(Request $request)
    {
        $query = FraudRule::query();

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->has('type')) {
            $query->byType($request->type);
        }

        $rules = $query->byPriority()->get();

        return response()->json([
            'success' => true,
            'rules' => $rules
        ]);
    }

    /**
     * Create fraud rule
     * POST /api/fraud/rules
     */
    public function createRule(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'rule_type' => 'required|in:velocity_check,amount_threshold,location_mismatch,device_fingerprint,behavioral_pattern,blacklist_match,high_risk_country,suspicious_email,multiple_cards,unusual_time',
            'conditions' => 'required|array',
            'risk_score' => 'required|integer|min:0|max:100',
            'action' => 'required|in:flag,review,block',
            'priority' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $rule = FraudRule::create($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Fraud rule created',
            'rule' => $rule
        ], 201);
    }

    /**
     * Update fraud rule
     * PUT /api/fraud/rules/{id}
     */
    public function updateRule($id, Request $request)
    {
        $rule = FraudRule::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'rule_type' => 'sometimes|in:velocity_check,amount_threshold,location_mismatch,device_fingerprint,behavioral_pattern,blacklist_match,high_risk_country,suspicious_email,multiple_cards,unusual_time',
            'conditions' => 'sometimes|array',
            'risk_score' => 'sometimes|integer|min:0|max:100',
            'action' => 'sometimes|in:flag,review,block',
            'priority' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $rule->update($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Fraud rule updated',
            'rule' => $rule->fresh()
        ]);
    }

    /**
     * Delete fraud rule
     * DELETE /api/fraud/rules/{id}
     */
    public function deleteRule($id)
    {
        $rule = FraudRule::findOrFail($id);
        $rule->delete();

        return response()->json([
            'success' => true,
            'message' => 'Fraud rule deleted'
        ]);
    }

    /**
     * Get blacklist entries
     * GET /api/fraud/blacklist
     */
    public function getBlacklist(Request $request)
    {
        $query = Blacklist::query();

        if ($request->has('type')) {
            $query->byType($request->type);
        }

        if ($request->has('severity')) {
            $query->bySeverity($request->severity);
        }

        if ($request->boolean('active_only', true)) {
            $query->active();
        }

        $perPage = $request->get('per_page', 50);
        $blacklist = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'blacklist' => $blacklist
        ]);
    }

    /**
     * Add to blacklist
     * POST /api/fraud/blacklist
     */
    public function addToBlacklist(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:ip,email,card_hash,device,phone,address_hash,user_id',
            'value' => 'required|string',
            'reason' => 'required|string|max:500',
            'severity' => 'nullable|in:low,medium,high,permanent',
            'expires_at' => 'nullable|date|after:now',
            'notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $entry = Blacklist::add(
            $request->type,
            $request->value,
            $request->reason,
            [
                'severity' => $request->get('severity', 'medium'),
                'expires_at' => $request->expires_at,
                'added_by' => auth()->id(),
                'notes' => $request->notes
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Added to blacklist',
            'entry' => $entry
        ], 201);
    }

    /**
     * Remove from blacklist
     * DELETE /api/fraud/blacklist/{id}
     */
    public function removeFromBlacklist($id)
    {
        $entry = Blacklist::findOrFail($id);
        $entry->remove();

        return response()->json([
            'success' => true,
            'message' => 'Removed from blacklist'
        ]);
    }

    /**
     * Check if value is blacklisted
     * POST /api/fraud/blacklist/check
     */
    public function checkBlacklist(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:ip,email,card_hash,device,phone,address_hash,user_id',
            'value' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $result = Blacklist::isBlacklisted($request->type, $request->value);

        return response()->json([
            'success' => true,
            'result' => $result
        ]);
    }

    /**
     * Get fraud attempts
     * GET /api/fraud/attempts
     */
    public function getAttempts(Request $request)
    {
        $query = FraudAttempt::query();

        if ($request->has('type')) {
            $query->byType($request->type);
        }

        if ($request->has('ip')) {
            $query->byIp($request->ip);
        }

        if ($request->boolean('blocked_only')) {
            $query->blocked();
        }

        if ($request->boolean('high_severity_only')) {
            $query->highSeverity();
        }

        $perPage = $request->get('per_page', 50);
        $attempts = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'attempts' => $attempts
        ]);
    }

    /**
     * Get fraud analytics
     * GET /api/fraud/analytics
     */
    public function getAnalytics(Request $request)
    {
        $days = $request->get('days', 30);
        $analytics = $this->fraudService->getAnalytics($days);

        return response()->json([
            'success' => true,
            'analytics' => $analytics
        ]);
    }

    /**
     * Bulk analyze orders
     * POST /api/fraud/bulk-analyze
     */
    public function bulkAnalyze(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_ids' => 'required|array',
            'order_ids.*' => 'required|integer|exists:orders,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $results = $this->fraudService->bulkAnalyze($request->order_ids);

        return response()->json([
            'success' => true,
            'results' => $results
        ]);
    }

    /**
     * Reanalyze order
     * POST /api/fraud/reanalyze/{orderId}
     */
    public function reanalyze($orderId)
    {
        $order = Order::findOrFail($orderId);
        $fraudScore = $this->fraudService->reanalyzeOrder($order);

        return response()->json([
            'success' => true,
            'message' => 'Order reanalyzed',
            'fraud_score' => $fraudScore,
            'analysis' => $fraudScore->getAnalysisSummary()
        ]);
    }

    /**
     * Get velocity stats for identifier
     * GET /api/fraud/velocity/{identifier}
     */
    public function getVelocityStats($identifier, Request $request)
    {
        $type = $request->get('type', 'ip');
        $hours = $request->get('hours', 24);

        $stats = VelocityTracking::getStats($identifier, $type, $hours);

        return response()->json([
            'success' => true,
            'velocity_stats' => $stats
        ]);
    }
}
