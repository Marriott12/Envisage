<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FraudRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'rule_type',
        'conditions',
        'risk_score',
        'action',
        'is_active',
        'priority',
        'trigger_count'
    ];

    protected $casts = [
        'conditions' => 'array',
        'is_active' => 'boolean',
        'risk_score' => 'integer',
        'priority' => 'integer',
        'trigger_count' => 'integer'
    ];

    /**
     * Evaluate if rule should trigger for given data
     */
    public function evaluate($data)
    {
        if (!$this->is_active) {
            return false;
        }

        $conditions = $this->conditions;
        
        switch ($this->rule_type) {
            case 'velocity_check':
                return $this->evaluateVelocity($data, $conditions);
            
            case 'amount_threshold':
                return $this->evaluateAmount($data, $conditions);
            
            case 'location_mismatch':
                return $this->evaluateLocation($data, $conditions);
            
            case 'device_fingerprint':
                return $this->evaluateDevice($data, $conditions);
            
            case 'behavioral_pattern':
                return $this->evaluateBehavior($data, $conditions);
            
            case 'blacklist_match':
                return $this->evaluateBlacklist($data, $conditions);
            
            case 'high_risk_country':
                return $this->evaluateCountry($data, $conditions);
            
            case 'suspicious_email':
                return $this->evaluateEmail($data, $conditions);
            
            case 'multiple_cards':
                return $this->evaluateMultipleCards($data, $conditions);
            
            case 'unusual_time':
                return $this->evaluateTime($data, $conditions);
            
            default:
                return false;
        }
    }

    /**
     * Velocity check: too many actions in short time
     */
    protected function evaluateVelocity($data, $conditions)
    {
        $identifier = $data[$conditions['identifier_field']] ?? null;
        if (!$identifier) return false;

        $window = $conditions['time_window'] ?? 3600; // seconds
        $threshold = $conditions['threshold'] ?? 5;
        $action = $conditions['action'] ?? 'order';

        $count = VelocityTracking::where('identifier', $identifier)
            ->where('action', $action)
            ->where('window_end', '>', now())
            ->sum('count');

        return $count >= $threshold;
    }

    /**
     * Amount threshold: unusually high order amount
     */
    protected function evaluateAmount($data, $conditions)
    {
        $amount = $data['order_amount'] ?? 0;
        $threshold = $conditions['threshold'] ?? 1000;
        $operator = $conditions['operator'] ?? '>';

        switch ($operator) {
            case '>': return $amount > $threshold;
            case '>=': return $amount >= $threshold;
            case '<': return $amount < $threshold;
            case '<=': return $amount <= $threshold;
            case '==': return $amount == $threshold;
            default: return false;
        }
    }

    /**
     * Location mismatch: IP location differs from billing address
     */
    protected function evaluateLocation($data, $conditions)
    {
        $ipCountry = $data['ip_country'] ?? null;
        $billingCountry = $data['billing_country'] ?? null;

        if (!$ipCountry || !$billingCountry) return false;

        return strtoupper($ipCountry) !== strtoupper($billingCountry);
    }

    /**
     * Device fingerprint: new or suspicious device
     */
    protected function evaluateDevice($data, $conditions)
    {
        $deviceFingerprint = $data['device_fingerprint'] ?? null;
        if (!$deviceFingerprint) return false;

        // Check if device is known for this user
        $userId = $data['user_id'] ?? null;
        if ($userId) {
            $knownDevice = Order::where('user_id', $userId)
                ->where('device_fingerprint', $deviceFingerprint)
                ->where('created_at', '<', now()->subDays(1))
                ->exists();

            return !$knownDevice;
        }

        return false;
    }

    /**
     * Behavioral pattern: unusual behavior
     */
    protected function evaluateBehavior($data, $conditions)
    {
        $userId = $data['user_id'] ?? null;
        if (!$userId) return false;

        // Check for unusual patterns
        $pattern = $conditions['pattern'] ?? 'first_order_high_value';

        if ($pattern === 'first_order_high_value') {
            $orderCount = Order::where('user_id', $userId)->count();
            $amount = $data['order_amount'] ?? 0;
            $threshold = $conditions['amount_threshold'] ?? 500;

            return $orderCount === 0 && $amount >= $threshold;
        }

        return false;
    }

    /**
     * Blacklist match: identifier is blacklisted
     */
    protected function evaluateBlacklist($data, $conditions)
    {
        $checkTypes = $conditions['check_types'] ?? ['ip', 'email'];

        foreach ($checkTypes as $type) {
            $value = $data[$type] ?? null;
            if (!$value) continue;

            // Hash sensitive data
            if (in_array($type, ['email', 'card_hash'])) {
                $value = hash('sha256', strtolower($value));
            }

            $blacklisted = Blacklist::where('type', $type)
                ->where('value', $value)
                ->where('is_active', true)
                ->where(function($q) {
                    $q->whereNull('expires_at')
                      ->orWhere('expires_at', '>', now());
                })
                ->exists();

            if ($blacklisted) return true;
        }

        return false;
    }

    /**
     * High risk country
     */
    protected function evaluateCountry($data, $conditions)
    {
        $country = $data['billing_country'] ?? $data['ip_country'] ?? null;
        if (!$country) return false;

        $highRiskCountries = $conditions['countries'] ?? [];
        return in_array(strtoupper($country), $highRiskCountries);
    }

    /**
     * Suspicious email patterns
     */
    protected function evaluateEmail($data, $conditions)
    {
        $email = $data['email'] ?? null;
        if (!$email) return false;

        $patterns = $conditions['patterns'] ?? [
            'temp_email',
            'disposable',
            'random_chars'
        ];

        foreach ($patterns as $pattern) {
            if ($pattern === 'temp_email') {
                $tempDomains = ['tempmail.com', 'guerrillamail.com', '10minutemail.com'];
                foreach ($tempDomains as $domain) {
                    if (str_contains($email, $domain)) return true;
                }
            }

            if ($pattern === 'random_chars') {
                // Check if local part has excessive random characters
                $localPart = explode('@', $email)[0];
                if (strlen($localPart) > 15 && preg_match('/[0-9]{5,}/', $localPart)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Multiple cards used by same user in short time
     */
    protected function evaluateMultipleCards($data, $conditions)
    {
        $userId = $data['user_id'] ?? null;
        if (!$userId) return false;

        $timeWindow = $conditions['time_window'] ?? 24; // hours
        $threshold = $conditions['threshold'] ?? 3;

        $uniqueCards = Order::where('user_id', $userId)
            ->where('created_at', '>', now()->subHours($timeWindow))
            ->distinct('card_last4')
            ->count('card_last4');

        return $uniqueCards >= $threshold;
    }

    /**
     * Unusual time: order placed at odd hours
     */
    protected function evaluateTime($data, $conditions)
    {
        $hour = now()->hour;
        $unusualStart = $conditions['unusual_start'] ?? 2; // 2 AM
        $unusualEnd = $conditions['unusual_end'] ?? 5; // 5 AM

        return $hour >= $unusualStart && $hour < $unusualEnd;
    }

    /**
     * Increment trigger count
     */
    public function incrementTriggerCount()
    {
        $this->increment('trigger_count');
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByPriority($query)
    {
        return $query->orderBy('priority', 'desc');
    }

    public function scopeByType($query, $type)
    {
        return $query->where('rule_type', $type);
    }
}
