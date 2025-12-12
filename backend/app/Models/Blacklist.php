<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Blacklist extends Model
{
    use HasFactory;

    protected $table = 'blacklist';

    protected $fillable = [
        'type',
        'value',
        'reason',
        'severity',
        'expires_at',
        'is_active',
        'hit_count',
        'added_by',
        'notes'
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
        'hit_count' => 'integer'
    ];

    /**
     * Relationships
     */
    public function addedBy()
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    /**
     * Add to blacklist
     */
    public static function add($type, $value, $reason, $options = [])
    {
        // Hash sensitive data
        if (in_array($type, ['email', 'card_hash'])) {
            $value = hash('sha256', strtolower($value));
        }

        // Check if already exists
        $existing = self::where('type', $type)
            ->where('value', $value)
            ->first();

        if ($existing) {
            // Update existing entry
            $existing->update([
                'reason' => $reason,
                'severity' => $options['severity'] ?? $existing->severity,
                'is_active' => true,
                'expires_at' => $options['expires_at'] ?? $existing->expires_at,
                'notes' => $options['notes'] ?? $existing->notes
            ]);
            return $existing;
        }

        // Create new entry
        return self::create([
            'type' => $type,
            'value' => $value,
            'reason' => $reason,
            'severity' => $options['severity'] ?? 'medium',
            'expires_at' => $options['expires_at'] ?? null,
            'is_active' => true,
            'added_by' => $options['added_by'] ?? auth()->id(),
            'notes' => $options['notes'] ?? null
        ]);
    }

    /**
     * Check if value is blacklisted
     */
    public static function isBlacklisted($type, $value)
    {
        // Hash sensitive data
        if (in_array($type, ['email', 'card_hash'])) {
            $value = hash('sha256', strtolower($value));
        }

        $entry = self::where('type', $type)
            ->where('value', $value)
            ->where('is_active', true)
            ->where(function($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            })
            ->first();

        if ($entry) {
            $entry->increment('hit_count');
            return [
                'blacklisted' => true,
                'reason' => $entry->reason,
                'severity' => $entry->severity,
                'entry' => $entry
            ];
        }

        return ['blacklisted' => false];
    }

    /**
     * Remove from blacklist
     */
    public function remove()
    {
        $this->update(['is_active' => false]);
    }

    /**
     * Check multiple values at once
     */
    public static function checkMultiple($checks)
    {
        $results = [];
        
        foreach ($checks as $type => $value) {
            if (is_array($value)) {
                foreach ($value as $v) {
                    $results[$type][] = self::isBlacklisted($type, $v);
                }
            } else {
                $results[$type] = self::isBlacklisted($type, $value);
            }
        }

        return $results;
    }

    /**
     * Auto-blacklist based on fraud attempts
     */
    public static function autoBlacklist($identifier, $identifierType, $reason)
    {
        $typeMap = [
            'ip' => 'ip',
            'user' => 'user_id',
            'device' => 'device',
            'email' => 'email'
        ];

        $type = $typeMap[$identifierType] ?? $identifierType;

        return self::add($type, $identifier, $reason, [
            'severity' => 'high',
            'expires_at' => now()->addDays(30), // 30-day temporary ban
            'added_by' => null, // System-generated
            'notes' => 'Auto-blacklisted by fraud detection system'
        ]);
    }

    /**
     * Clean up expired entries
     */
    public static function cleanupExpired()
    {
        return self::where('is_active', true)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->update(['is_active' => false]);
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            });
    }

    public function scopeExpired($query)
    {
        return $query->where('is_active', true)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now());
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeBySeverity($query, $severity)
    {
        return $query->where('severity', $severity);
    }

    public function scopePermanent($query)
    {
        return $query->where('severity', 'permanent')
            ->orWhereNull('expires_at');
    }

    /**
     * Get blacklist statistics
     */
    public static function getStatistics()
    {
        return [
            'total_active' => self::active()->count(),
            'by_type' => self::active()
                ->selectRaw('type, COUNT(*) as count')
                ->groupBy('type')
                ->pluck('count', 'type'),
            'by_severity' => self::active()
                ->selectRaw('severity, COUNT(*) as count')
                ->groupBy('severity')
                ->pluck('count', 'severity'),
            'permanent_bans' => self::permanent()->count(),
            'temporary_bans' => self::active()
                ->whereNotNull('expires_at')
                ->where('severity', '!=', 'permanent')
                ->count(),
            'most_hit' => self::active()
                ->orderBy('hit_count', 'desc')
                ->limit(10)
                ->get(['type', 'value', 'hit_count', 'reason']),
            'expired_pending_cleanup' => self::expired()->count()
        ];
    }
}
