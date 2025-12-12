<?php

namespace App\Services;

use App\Models\Order;
use App\Models\User;
use App\Models\FraudRule;
use App\Models\FraudScore;
use App\Models\FraudAttempt;
use App\Models\Blacklist;
use App\Models\VelocityTracking;
use Illuminate\Support\Facades\Log;

class FraudDetectionService
{
    /**
     * Analyze order for fraud
     */
    public function analyzeOrder(Order $order)
    {
        try {
            // Gather analysis data
            $analysisData = $this->gatherAnalysisData($order);

            // Check blacklist first
            $blacklistCheck = $this->checkBlacklist($analysisData);
            if ($blacklistCheck['is_blacklisted']) {
                return $this->createCriticalScore($order, $blacklistCheck);
            }

            // Track velocity
            $this->trackVelocity($analysisData);

            // Evaluate fraud rules
            $ruleResults = $this->evaluateRules($analysisData);

            // Calculate total score
            $totalScore = $this->calculateTotalScore($ruleResults);

            // Determine risk level
            $riskLevel = $this->determineRiskLevel($totalScore);

            // Create fraud score record
            $fraudScore = FraudScore::create([
                'order_id' => $order->id,
                'user_id' => $order->user_id,
                'total_score' => $totalScore,
                'risk_level' => $riskLevel,
                'triggered_rules' => array_column($ruleResults['triggered'], 'rule_id'),
                'score_breakdown' => $ruleResults,
                'analysis_data' => $analysisData,
                'status' => $this->determineInitialStatus($riskLevel)
            ]);

            // Log fraud attempt if high risk
            if (in_array($riskLevel, ['high', 'critical'])) {
                $this->logFraudAttempt($order, $analysisData, $riskLevel);
            }

            // Take automated action
            $this->takeAutomatedAction($order, $fraudScore);

            // Check if should auto-blacklist
            $this->checkAutoBlacklist($analysisData, $riskLevel);

            return $fraudScore;

        } catch (\Exception $e) {
            Log::error('Fraud detection error', [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);

            // Default to safe - flag for manual review
            return FraudScore::create([
                'order_id' => $order->id,
                'user_id' => $order->user_id,
                'total_score' => 50,
                'risk_level' => 'medium',
                'triggered_rules' => [],
                'score_breakdown' => ['error' => $e->getMessage()],
                'analysis_data' => [],
                'status' => 'under_review'
            ]);
        }
    }

    /**
     * Gather comprehensive analysis data
     */
    protected function gatherAnalysisData(Order $order)
    {
        $user = $order->user;
        
        return [
            'order_id' => $order->id,
            'user_id' => $order->user_id,
            'order_amount' => $order->total_amount,
            'email' => $user->email ?? $order->billing_email,
            'ip_address' => $order->ip_address ?? request()->ip(),
            'user_agent' => $order->user_agent ?? request()->userAgent(),
            'device_fingerprint' => $order->device_fingerprint ?? null,
            'billing_country' => $order->billing_country,
            'shipping_country' => $order->shipping_country,
            'ip_country' => $this->getIpCountry($order->ip_address),
            'card_last4' => $order->card_last4 ?? null,
            'payment_method' => $order->payment_method,
            'order_time' => $order->created_at,
            'user_created_at' => $user->created_at ?? null,
            'user_orders_count' => $user ? Order::where('user_id', $user->id)->count() : 0,
            'user_lifetime_value' => $user ? Order::where('user_id', $user->id)->sum('total_amount') : 0,
            'shipping_billing_match' => $this->compareAddresses($order),
            'order_items_count' => $order->items->count(),
            'high_value_items' => $order->items->where('price', '>', 500)->count(),
            'digital_items' => $order->items->filter(function($item) {
                return $item->product->type === 'digital';
            })->count()
        ];
    }

    /**
     * Check blacklist for multiple identifiers
     */
    protected function checkBlacklist($data)
    {
        $checks = [
            'ip' => $data['ip_address'],
            'email' => $data['email'],
            'user_id' => $data['user_id']
        ];

        if (!empty($data['device_fingerprint'])) {
            $checks['device'] = $data['device_fingerprint'];
        }

        $results = Blacklist::checkMultiple($checks);

        foreach ($results as $type => $result) {
            if (is_array($result) && isset($result['blacklisted']) && $result['blacklisted']) {
                return [
                    'is_blacklisted' => true,
                    'type' => $type,
                    'reason' => $result['reason'],
                    'severity' => $result['severity']
                ];
            }
        }

        return ['is_blacklisted' => false];
    }

    /**
     * Track velocity for this order
     */
    protected function trackVelocity($data)
    {
        // Track by user
        if (!empty($data['user_id'])) {
            VelocityTracking::track($data['user_id'], 'user', 'order', 60);
        }

        // Track by IP
        VelocityTracking::track($data['ip_address'], 'ip', 'order', 60);

        // Track by email
        VelocityTracking::track($data['email'], 'email', 'order', 60);

        // Track by device
        if (!empty($data['device_fingerprint'])) {
            VelocityTracking::track($data['device_fingerprint'], 'device', 'order', 60);
        }
    }

    /**
     * Evaluate all active fraud rules
     */
    protected function evaluateRules($data)
    {
        $rules = FraudRule::active()->byPriority()->get();
        
        $triggered = [];
        $notTriggered = [];

        foreach ($rules as $rule) {
            if ($rule->evaluate($data)) {
                $triggered[] = [
                    'rule_id' => $rule->id,
                    'rule_name' => $rule->name,
                    'rule_type' => $rule->rule_type,
                    'score' => $rule->risk_score,
                    'action' => $rule->action
                ];
                $rule->incrementTriggerCount();
            } else {
                $notTriggered[] = [
                    'rule_id' => $rule->id,
                    'rule_name' => $rule->name
                ];
            }
        }

        return [
            'triggered' => $triggered,
            'not_triggered' => $notTriggered,
            'total_rules_checked' => $rules->count()
        ];
    }

    /**
     * Calculate total fraud score
     */
    protected function calculateTotalScore($ruleResults)
    {
        $score = 0;
        
        foreach ($ruleResults['triggered'] as $result) {
            $score += $result['score'];
        }

        // Cap at 100
        return min(100, $score);
    }

    /**
     * Determine risk level from score
     */
    protected function determineRiskLevel($score)
    {
        if ($score >= 80) return 'critical';
        if ($score >= 60) return 'high';
        if ($score >= 40) return 'medium';
        return 'low';
    }

    /**
     * Determine initial status based on risk
     */
    protected function determineInitialStatus($riskLevel)
    {
        switch ($riskLevel) {
            case 'critical':
            case 'high':
                return 'under_review';
            case 'medium':
                return 'pending';
            case 'low':
            default:
                return 'approved';
        }
    }

    /**
     * Create critical score for blacklisted order
     */
    protected function createCriticalScore(Order $order, $blacklistCheck)
    {
        FraudAttempt::logAttempt('blacklist_match', [
            'order_id' => $order->id,
            'user_id' => $order->user_id,
            'blacklist_type' => $blacklistCheck['type'],
            'blacklist_reason' => $blacklistCheck['reason'],
            'blocked' => true,
            'block_reason' => 'Blacklisted ' . $blacklistCheck['type']
        ]);

        $fraudScore = FraudScore::create([
            'order_id' => $order->id,
            'user_id' => $order->user_id,
            'total_score' => 100,
            'risk_level' => 'critical',
            'triggered_rules' => [],
            'score_breakdown' => ['blacklist' => $blacklistCheck],
            'analysis_data' => $blacklistCheck,
            'status' => 'rejected'
        ]);

        // Auto-reject order
        $order->update(['status' => 'cancelled']);

        return $fraudScore;
    }

    /**
     * Log fraud attempt for high-risk orders
     */
    protected function logFraudAttempt(Order $order, $data, $riskLevel)
    {
        $attemptType = $this->determineAttemptType($data);

        FraudAttempt::logAttempt($attemptType, [
            'order_id' => $order->id,
            'user_id' => $data['user_id'],
            'ip_address' => $data['ip_address'],
            'device_fingerprint' => $data['device_fingerprint'],
            'amount' => $data['order_amount'],
            'risk_level' => $riskLevel
        ]);
    }

    /**
     * Determine fraud attempt type based on patterns
     */
    protected function determineAttemptType($data)
    {
        // Check for card testing (low amount, new user)
        if ($data['order_amount'] < 10 && $data['user_orders_count'] === 0) {
            return 'card_testing';
        }

        // Check for high-value first order
        if ($data['order_amount'] > 500 && $data['user_orders_count'] === 0) {
            return 'identity_theft';
        }

        // Check for multiple digital items (common in fraud)
        if ($data['digital_items'] > 0 && $data['digital_items'] === $data['order_items_count']) {
            return 'friendly_fraud';
        }

        return 'bot_activity';
    }

    /**
     * Take automated action based on risk
     */
    protected function takeAutomatedAction(Order $order, FraudScore $fraudScore)
    {
        switch ($fraudScore->risk_level) {
            case 'critical':
                // Auto-cancel critical risk orders
                $order->update(['status' => 'cancelled']);
                $fraudScore->update(['status' => 'rejected']);
                break;

            case 'high':
                // Hold for manual review
                $order->update(['status' => 'pending_fraud_review']);
                $fraudScore->update(['status' => 'under_review']);
                break;

            case 'medium':
                // Flag but allow processing
                $order->update(['fraud_flagged' => true]);
                break;

            case 'low':
                // Approve automatically
                $fraudScore->update(['status' => 'approved']);
                break;
        }
    }

    /**
     * Check if should auto-blacklist
     */
    protected function checkAutoBlacklist($data, $riskLevel)
    {
        if (!in_array($riskLevel, ['high', 'critical'])) {
            return;
        }

        // Check IP for multiple fraud attempts
        if (FraudAttempt::shouldBlacklistIp($data['ip_address'])) {
            Blacklist::autoBlacklist($data['ip_address'], 'ip', 'Multiple fraud attempts detected');
        }

        // Check user for multiple fraud attempts
        if (!empty($data['user_id']) && FraudAttempt::shouldBlacklistUser($data['user_id'])) {
            Blacklist::autoBlacklist($data['user_id'], 'user', 'Multiple fraud attempts detected');
        }
    }

    /**
     * Get IP country (mock - integrate with real IP geolocation service)
     */
    protected function getIpCountry($ip)
    {
        // TODO: Integrate with IP geolocation service (MaxMind, IPStack, etc.)
        return 'US'; // Default
    }

    /**
     * Compare shipping and billing addresses
     */
    protected function compareAddresses(Order $order)
    {
        if (!$order->shipping_address || !$order->billing_address) {
            return false;
        }

        $shippingNormalized = strtolower(trim($order->shipping_address));
        $billingNormalized = strtolower(trim($order->billing_address));

        return $shippingNormalized === $billingNormalized;
    }

    /**
     * Get fraud analytics for dashboard
     */
    public function getAnalytics($days = 30)
    {
        return [
            'fraud_scores' => FraudScore::getStatistics($days),
            'fraud_attempts' => FraudAttempt::getStatistics($days),
            'blacklist' => Blacklist::getStatistics(),
            'velocity' => $this->getVelocityStats($days)
        ];
    }

    /**
     * Get velocity statistics
     */
    protected function getVelocityStats($days)
    {
        $activeWindows = VelocityTracking::active()->count();
        $totalActions = VelocityTracking::where('created_at', '>', now()->subDays($days))
            ->sum('count');

        return [
            'active_windows' => $activeWindows,
            'total_actions_tracked' => $totalActions
        ];
    }

    /**
     * Bulk analyze orders
     */
    public function bulkAnalyze($orderIds)
    {
        $results = [];

        foreach ($orderIds as $orderId) {
            $order = Order::find($orderId);
            if ($order) {
                $results[$orderId] = $this->analyzeOrder($order);
            }
        }

        return $results;
    }

    /**
     * Reanalyze order (for testing rule changes)
     */
    public function reanalyzeOrder(Order $order)
    {
        // Delete old fraud score
        FraudScore::where('order_id', $order->id)->delete();

        // Analyze again
        return $this->analyzeOrder($order);
    }
}
