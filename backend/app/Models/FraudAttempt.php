<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FraudAttempt extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'order_id',
        'attempt_type',
        'ip_address',
        'user_agent',
        'device_fingerprint',
        'attempt_data',
        'severity',
        'blocked',
        'block_reason'
    ];

    protected $casts = [
        'attempt_data' => 'array',
        'severity' => 'integer',
        'blocked' => 'boolean'
    ];

    /**
     * Relationships
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Log a fraud attempt
     */
    public static function logAttempt($type, $data = [])
    {
        $severity = self::calculateSeverity($type, $data);

        return self::create([
            'user_id' => $data['user_id'] ?? null,
            'order_id' => $data['order_id'] ?? null,
            'attempt_type' => $type,
            'ip_address' => $data['ip_address'] ?? request()->ip(),
            'user_agent' => $data['user_agent'] ?? request()->userAgent(),
            'device_fingerprint' => $data['device_fingerprint'] ?? null,
            'attempt_data' => $data,
            'severity' => $severity,
            'blocked' => $data['blocked'] ?? false,
            'block_reason' => $data['block_reason'] ?? null
        ]);
    }

    /**
     * Calculate severity based on attempt type and data
     */
    protected static function calculateSeverity($type, $data)
    {
        $baseSeverity = [
            'multiple_failed_payments' => 6,
            'card_testing' => 8,
            'account_takeover' => 9,
            'promo_abuse' => 4,
            'refund_fraud' => 7,
            'friendly_fraud' => 5,
            'identity_theft' => 10,
            'bot_activity' => 5,
            'credential_stuffing' => 8
        ];

        $severity = $baseSeverity[$type] ?? 5;

        // Adjust based on data
        if (isset($data['repeat_offender']) && $data['repeat_offender']) {
            $severity = min(10, $severity + 2);
        }

        if (isset($data['amount']) && $data['amount'] > 1000) {
            $severity = min(10, $severity + 1);
        }

        return $severity;
    }

    /**
     * Check if IP should be blacklisted
     */
    public static function shouldBlacklistIp($ip, $timeWindow = 24)
    {
        $attempts = self::where('ip_address', $ip)
            ->where('created_at', '>', now()->subHours($timeWindow))
            ->where('severity', '>=', 7)
            ->count();

        return $attempts >= 3;
    }

    /**
     * Check if user should be blacklisted
     */
    public static function shouldBlacklistUser($userId, $timeWindow = 24)
    {
        $attempts = self::where('user_id', $userId)
            ->where('created_at', '>', now()->subHours($timeWindow))
            ->where('severity', '>=', 7)
            ->count();

        return $attempts >= 3;
    }

    /**
     * Get recent attempts by identifier
     */
    public static function getRecentAttempts($identifier, $identifierType = 'ip', $hours = 24)
    {
        $field = $identifierType === 'ip' ? 'ip_address' : 
                ($identifierType === 'user' ? 'user_id' : 'device_fingerprint');

        return self::where($field, $identifier)
            ->where('created_at', '>', now()->subHours($hours))
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Scopes
     */
    public function scopeBlocked($query)
    {
        return $query->where('blocked', true);
    }

    public function scopeHighSeverity($query)
    {
        return $query->where('severity', '>=', 7);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('attempt_type', $type);
    }

    public function scopeByIp($query, $ip)
    {
        return $query->where('ip_address', $ip);
    }

    public function scopeRecent($query, $hours = 24)
    {
        return $query->where('created_at', '>', now()->subHours($hours));
    }

    /**
     * Get fraud attempt statistics
     */
    public static function getStatistics($days = 30)
    {
        $startDate = now()->subDays($days);

        return [
            'total_attempts' => self::where('created_at', '>', $startDate)->count(),
            'blocked_attempts' => self::where('created_at', '>', $startDate)
                ->where('blocked', true)
                ->count(),
            'by_type' => self::where('created_at', '>', $startDate)
                ->selectRaw('attempt_type, COUNT(*) as count')
                ->groupBy('attempt_type')
                ->pluck('count', 'attempt_type'),
            'high_severity' => self::where('created_at', '>', $startDate)
                ->where('severity', '>=', 7)
                ->count(),
            'unique_ips' => self::where('created_at', '>', $startDate)
                ->distinct('ip_address')
                ->count('ip_address'),
            'repeat_offenders' => self::where('created_at', '>', $startDate)
                ->selectRaw('ip_address, COUNT(*) as count')
                ->groupBy('ip_address')
                ->having('count', '>=', 3)
                ->count()
        ];
    }
}
