<?php

namespace App\Services;

use App\Models\Order;
use App\Models\User;
use App\Models\FraudAlert;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Advanced Machine Learning Fraud Detection Service
 * 
 * Features:
 * - Ensemble ML models (Random Forest, XGBoost, Neural Networks)
 * - Anomaly detection using Isolation Forest
 * - Transaction pattern analysis
 * - Device fingerprinting
 * - Behavioral biometrics
 * - Real-time risk scoring
 * - Graph-based fraud detection
 */
class AdvancedFraudDetectionService
{
    protected $mlServiceUrl;
    protected $riskThresholds = [
        'low' => 0.3,
        'medium' => 0.6,
        'high' => 0.8,
    ];

    public function __construct()
    {
        $this->mlServiceUrl = config('services.ml.url', env('ML_SERVICE_URL', 'http://localhost:5000'));
    }

    /**
     * Comprehensive fraud check using ensemble models
     */
    public function checkTransaction($transactionData)
    {
        // Extract features
        $features = $this->extractFeatures($transactionData);

        // Get ML predictions
        $mlScore = $this->getMLFraudScore($features);

        // Get rule-based score
        $ruleScore = $this->getRuleBasedScore($transactionData);

        // Get anomaly detection score
        $anomalyScore = $this->getAnomalyScore($features);

        // Get graph-based score
        $graphScore = $this->getGraphBasedScore($transactionData);

        // Ensemble: weighted average
        $finalScore = (
            $mlScore * 0.4 +
            $ruleScore * 0.25 +
            $anomalyScore * 0.2 +
            $graphScore * 0.15
        );

        $riskLevel = $this->getRiskLevel($finalScore);

        // Log if high risk
        if ($riskLevel === 'high' || $riskLevel === 'critical') {
            $this->logFraudAlert($transactionData, $finalScore, [
                'ml_score' => $mlScore,
                'rule_score' => $ruleScore,
                'anomaly_score' => $anomalyScore,
                'graph_score' => $graphScore,
            ]);
        }

        return [
            'risk_score' => $finalScore,
            'risk_level' => $riskLevel,
            'should_review' => $finalScore >= $this->riskThresholds['medium'],
            'should_block' => $finalScore >= $this->riskThresholds['high'],
            'details' => [
                'ml_score' => $mlScore,
                'rule_based' => $ruleScore,
                'anomaly_score' => $anomalyScore,
                'graph_score' => $graphScore,
            ],
            'reasons' => $this->getFraudReasons($transactionData, $finalScore),
        ];
    }

    /**
     * Get ML fraud score from Python service
     */
    protected function getMLFraudScore($features)
    {
        try {
            $response = Http::timeout(5)->post("{$this->mlServiceUrl}/api/fraud/predict", [
                'features' => $features,
                'model' => 'ensemble', // XGBoost + Neural Network + Random Forest
            ]);

            if ($response->successful()) {
                return $response->json()['fraud_probability'];
            }
        } catch (\Exception $e) {
            \Log::warning("ML fraud detection failed: " . $e->getMessage());
        }

        return 0.5; // Neutral score on failure
    }

    /**
     * Rule-based fraud detection
     */
    protected function getRuleBasedScore($data)
    {
        $score = 0.0;
        $flags = 0;

        // Velocity checks
        if ($this->checkVelocity($data['user_id'])) {
            $score += 0.3;
            $flags++;
        }

        // Amount anomaly
        if ($this->checkAmountAnomaly($data['user_id'], $data['amount'])) {
            $score += 0.25;
            $flags++;
        }

        // Suspicious patterns
        if ($this->checkSuspiciousPatterns($data)) {
            $score += 0.2;
            $flags++;
        }

        // Geographic anomaly
        if ($this->checkGeographicAnomaly($data['user_id'], $data['ip_address'] ?? null)) {
            $score += 0.15;
            $flags++;
        }

        // Time anomaly (unusual hour)
        if ($this->checkTimeAnomaly($data['user_id'])) {
            $score += 0.1;
            $flags++;
        }

        return min($score, 1.0);
    }

    /**
     * Anomaly detection using Isolation Forest
     */
    protected function getAnomalyScore($features)
    {
        try {
            $response = Http::timeout(5)->post("{$this->mlServiceUrl}/api/fraud/anomaly", [
                'features' => $features,
                'model' => 'isolation_forest',
            ]);

            if ($response->successful()) {
                // Returns -1 for anomalies, 1 for normal
                $anomalyScore = $response->json()['anomaly_score'];
                
                // Convert to 0-1 scale (0 = normal, 1 = anomaly)
                return (1 - $anomalyScore) / 2;
            }
        } catch (\Exception $e) {
            \Log::warning("Anomaly detection failed: " . $e->getMessage());
        }

        return 0.0;
    }

    /**
     * Graph-based fraud detection
     * Analyzes network of users, devices, and addresses
     */
    protected function getGraphBasedScore($data)
    {
        try {
            $response = Http::timeout(5)->post("{$this->mlServiceUrl}/api/fraud/graph", [
                'user_id' => $data['user_id'],
                'device_fingerprint' => $data['device_fingerprint'] ?? null,
                'ip_address' => $data['ip_address'] ?? null,
                'billing_address' => $data['billing_address'] ?? null,
                'shipping_address' => $data['shipping_address'] ?? null,
            ]);

            if ($response->successful()) {
                return $response->json()['risk_score'];
            }
        } catch (\Exception $e) {
            \Log::warning("Graph-based fraud detection failed: " . $e->getMessage());
        }

        // Fallback: check for shared devices/IPs
        return $this->basicGraphCheck($data);
    }

    /**
     * Basic graph check (fallback)
     */
    protected function basicGraphCheck($data)
    {
        $score = 0.0;

        if (isset($data['device_fingerprint'])) {
            $deviceUsers = Order::where('device_fingerprint', $data['device_fingerprint'])
                ->distinct('user_id')
                ->count();
                
            if ($deviceUsers > 5) {
                $score += 0.3; // Same device used by many users
            }
        }

        if (isset($data['ip_address'])) {
            $ipUsers = Order::where('ip_address', $data['ip_address'])
                ->where('created_at', '>=', now()->subDays(7))
                ->distinct('user_id')
                ->count();
                
            if ($ipUsers > 10) {
                $score += 0.3; // Same IP for many users
            }
        }

        return min($score, 1.0);
    }

    /**
     * Extract features for ML models
     */
    protected function extractFeatures($data)
    {
        $user = User::find($data['user_id']);
        
        return [
            // Transaction features
            'amount' => $data['amount'],
            'hour_of_day' => (int)date('H'),
            'day_of_week' => (int)date('w'),
            'is_weekend' => in_array(date('w'), [0, 6]) ? 1 : 0,
            
            // User history features
            'account_age_days' => $user ? $user->created_at->diffInDays(now()) : 0,
            'total_orders' => $this->getUserOrderCount($data['user_id']),
            'avg_order_amount' => $this->getUserAvgOrderAmount($data['user_id']),
            'orders_last_24h' => $this->getUserRecentOrderCount($data['user_id'], 24),
            'orders_last_7d' => $this->getUserRecentOrderCount($data['user_id'], 168),
            
            // Velocity features
            'transactions_per_hour' => $this->getTransactionVelocity($data['user_id'], 1),
            'transactions_per_day' => $this->getTransactionVelocity($data['user_id'], 24),
            
            // Amount deviation
            'amount_vs_avg' => $this->getAmountDeviation($data['user_id'], $data['amount']),
            
            // Device/location features
            'is_new_device' => $this->isNewDevice($data['user_id'], $data['device_fingerprint'] ?? null),
            'is_new_ip' => $this->isNewIP($data['user_id'], $data['ip_address'] ?? null),
            'ip_distance_km' => $this->getIPDistanceFromUsual($data['user_id'], $data['ip_address'] ?? null),
            
            // Behavioral features
            'time_since_last_order' => $this->getTimeSinceLastOrder($data['user_id']),
            'email_verified' => $user ? ($user->email_verified_at ? 1 : 0) : 0,
            'phone_verified' => $user ? ($user->phone_verified_at ?? 0 ? 1 : 0) : 0,
        ];
    }

    /**
     * Velocity check: too many transactions too quickly
     */
    protected function checkVelocity($userId)
    {
        $last1Hour = Order::where('user_id', $userId)
            ->where('created_at', '>=', now()->subHour())
            ->count();

        $last24Hours = Order::where('user_id', $userId)
            ->where('created_at', '>=', now()->subDay())
            ->count();

        return $last1Hour >= 5 || $last24Hours >= 20;
    }

    /**
     * Check for unusual transaction amount
     */
    protected function checkAmountAnomaly($userId, $amount)
    {
        $avgAmount = $this->getUserAvgOrderAmount($userId);
        
        if ($avgAmount === 0) {
            return $amount > 1000; // High first purchase
        }

        $deviation = abs($amount - $avgAmount) / $avgAmount;
        
        return $deviation > 3.0; // 3x higher/lower than average
    }

    /**
     * Check for suspicious patterns
     */
    protected function checkSuspiciousPatterns($data)
    {
        $suspicious = false;

        // Round number amounts (e.g., exactly $500.00)
        if ($data['amount'] == floor($data['amount']) && $data['amount'] >= 100) {
            $suspicious = true;
        }

        // Shipping != billing (if available)
        if (isset($data['billing_address']) && isset($data['shipping_address'])) {
            if ($this->addressesDiffer($data['billing_address'], $data['shipping_address'])) {
                $suspicious = true;
            }
        }

        return $suspicious;
    }

    /**
     * Check for geographic anomaly
     */
    protected function checkGeographicAnomaly($userId, $ipAddress)
    {
        if (!$ipAddress) {
            return false;
        }

        $recentOrders = Order::where('user_id', $userId)
            ->where('created_at', '>=', now()->subDays(30))
            ->whereNotNull('ip_address')
            ->limit(10)
            ->pluck('ip_address')
            ->toArray();

        if (empty($recentOrders)) {
            return false;
        }

        // Check if IP is very different from usual
        $usualCountry = $this->getIPCountry($recentOrders[0] ?? $ipAddress);
        $currentCountry = $this->getIPCountry($ipAddress);

        return $usualCountry !== $currentCountry;
    }

    /**
     * Check for unusual transaction time
     */
    protected function checkTimeAnomaly($userId)
    {
        $hour = (int)date('H');
        
        // Unusual hours (2 AM - 6 AM)
        if ($hour >= 2 && $hour <= 6) {
            return true;
        }

        return false;
    }

    /**
     * Get risk level from score
     */
    protected function getRiskLevel($score)
    {
        if ($score >= 0.9) return 'critical';
        if ($score >= $this->riskThresholds['high']) return 'high';
        if ($score >= $this->riskThresholds['medium']) return 'medium';
        if ($score >= $this->riskThresholds['low']) return 'low';
        return 'minimal';
    }

    /**
     * Get fraud reasons
     */
    protected function getFraudReasons($data, $score)
    {
        $reasons = [];

        if ($this->checkVelocity($data['user_id'])) {
            $reasons[] = 'High transaction velocity';
        }

        if ($this->checkAmountAnomaly($data['user_id'], $data['amount'])) {
            $reasons[] = 'Unusual transaction amount';
        }

        if ($this->checkGeographicAnomaly($data['user_id'], $data['ip_address'] ?? null)) {
            $reasons[] = 'Geographic anomaly detected';
        }

        if ($this->checkTimeAnomaly($data['user_id'])) {
            $reasons[] = 'Unusual transaction time';
        }

        return $reasons;
    }

    /**
     * Log fraud alert
     */
    protected function logFraudAlert($data, $score, $details)
    {
        FraudAlert::create([
            'user_id' => $data['user_id'],
            'order_id' => $data['order_id'] ?? null,
            'risk_score' => $score,
            'risk_level' => $this->getRiskLevel($score),
            'details' => json_encode($details),
            'reasons' => json_encode($this->getFraudReasons($data, $score)),
            'status' => 'pending_review',
        ]);
    }

    // Helper methods
    protected function getUserOrderCount($userId)
    {
        return Cache::remember("user_order_count:{$userId}", 3600, function () use ($userId) {
            return Order::where('user_id', $userId)->count();
        });
    }

    protected function getUserAvgOrderAmount($userId)
    {
        return Cache::remember("user_avg_amount:{$userId}", 3600, function () use ($userId) {
            return Order::where('user_id', $userId)->avg('total_amount') ?? 0;
        });
    }

    protected function getUserRecentOrderCount($userId, $hours)
    {
        return Order::where('user_id', $userId)
            ->where('created_at', '>=', now()->subHours($hours))
            ->count();
    }

    protected function getTransactionVelocity($userId, $hours)
    {
        return $this->getUserRecentOrderCount($userId, $hours);
    }

    protected function getAmountDeviation($userId, $amount)
    {
        $avg = $this->getUserAvgOrderAmount($userId);
        return $avg > 0 ? ($amount - $avg) / $avg : 0;
    }

    protected function isNewDevice($userId, $deviceFingerprint)
    {
        if (!$deviceFingerprint) return false;
        
        return !Order::where('user_id', $userId)
            ->where('device_fingerprint', $deviceFingerprint)
            ->exists();
    }

    protected function isNewIP($userId, $ipAddress)
    {
        if (!$ipAddress) return false;
        
        return !Order::where('user_id', $userId)
            ->where('ip_address', $ipAddress)
            ->exists();
    }

    protected function getIPDistanceFromUsual($userId, $ipAddress)
    {
        // Simplified - would use GeoIP service
        return 0;
    }

    protected function getTimeSinceLastOrder($userId)
    {
        $lastOrder = Order::where('user_id', $userId)
            ->orderByDesc('created_at')
            ->first();

        return $lastOrder ? now()->diffInHours($lastOrder->created_at) : 999999;
    }

    protected function addressesDiffer($billing, $shipping)
    {
        return json_encode($billing) !== json_encode($shipping);
    }

    protected function getIPCountry($ipAddress)
    {
        // Would use GeoIP service - simplified
        return 'US';
    }

    /**
     * Device fingerprinting
     */
    public function generateDeviceFingerprint($request)
    {
        $components = [
            $request->header('User-Agent'),
            $request->header('Accept-Language'),
            $request->header('Accept-Encoding'),
            $request->ip(),
        ];

        return hash('sha256', implode('|', array_filter($components)));
    }

    /**
     * Behavioral biometrics analysis
     */
    public function analyzeBehavior($userId, $behaviorData)
    {
        // Analyze typing speed, mouse movements, etc.
        try {
            $response = Http::timeout(5)->post("{$this->mlServiceUrl}/api/fraud/behavior", [
                'user_id' => $userId,
                'typing_patterns' => $behaviorData['typing'] ?? [],
                'mouse_patterns' => $behaviorData['mouse'] ?? [],
                'session_duration' => $behaviorData['session_duration'] ?? 0,
            ]);

            if ($response->successful()) {
                return $response->json()['is_genuine'];
            }
        } catch (\Exception $e) {
            \Log::warning("Behavioral analysis failed: " . $e->getMessage());
        }

        return true; // Assume genuine on failure
    }
}
