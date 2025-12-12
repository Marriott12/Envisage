<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VelocityTracking extends Model
{
    use HasFactory;

    protected $table = 'velocity_tracking';

    protected $fillable = [
        'identifier',
        'identifier_type',
        'action',
        'count',
        'window_start',
        'window_end',
        'metadata'
    ];

    protected $casts = [
        'window_start' => 'datetime',
        'window_end' => 'datetime',
        'metadata' => 'array',
        'count' => 'integer'
    ];

    /**
     * Track an action
     */
    public static function track($identifier, $identifierType, $action, $windowMinutes = 60, $metadata = [])
    {
        $windowStart = now()->startOfMinute();
        $windowEnd = now()->addMinutes($windowMinutes);

        // Find existing window or create new
        $tracking = self::where('identifier', $identifier)
            ->where('identifier_type', $identifierType)
            ->where('action', $action)
            ->where('window_end', '>', now())
            ->first();

        if ($tracking) {
            // Increment existing window
            $tracking->increment('count');
            $tracking->update([
                'metadata' => array_merge($tracking->metadata ?? [], $metadata)
            ]);
        } else {
            // Create new window
            $tracking = self::create([
                'identifier' => $identifier,
                'identifier_type' => $identifierType,
                'action' => $action,
                'count' => 1,
                'window_start' => $windowStart,
                'window_end' => $windowEnd,
                'metadata' => $metadata
            ]);
        }

        return $tracking;
    }

    /**
     * Check if velocity limit exceeded
     */
    public static function checkLimit($identifier, $identifierType, $action, $limit, $windowMinutes = 60)
    {
        $count = self::where('identifier', $identifier)
            ->where('identifier_type', $identifierType)
            ->where('action', $action)
            ->where('window_end', '>', now())
            ->sum('count');

        return [
            'exceeded' => $count >= $limit,
            'current_count' => $count,
            'limit' => $limit,
            'remaining' => max(0, $limit - $count)
        ];
    }

    /**
     * Get current count for identifier/action
     */
    public static function getCurrentCount($identifier, $identifierType, $action)
    {
        return self::where('identifier', $identifier)
            ->where('identifier_type', $identifierType)
            ->where('action', $action)
            ->where('window_end', '>', now())
            ->sum('count');
    }

    /**
     * Clean up expired windows
     */
    public static function cleanup($olderThanHours = 24)
    {
        return self::where('window_end', '<', now()->subHours($olderThanHours))
            ->delete();
    }

    /**
     * Get velocity statistics for identifier
     */
    public static function getStats($identifier, $identifierType, $hours = 24)
    {
        $records = self::where('identifier', $identifier)
            ->where('identifier_type', $identifierType)
            ->where('created_at', '>', now()->subHours($hours))
            ->get();

        $byAction = $records->groupBy('action')->map(function($group) {
            return $group->sum('count');
        });

        return [
            'identifier' => $identifier,
            'identifier_type' => $identifierType,
            'time_window' => $hours . ' hours',
            'total_actions' => $records->sum('count'),
            'by_action' => $byAction,
            'unique_actions' => $byAction->count()
        ];
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('window_end', '>', now());
    }

    public function scopeExpired($query)
    {
        return $query->where('window_end', '<', now());
    }

    public function scopeForIdentifier($query, $identifier, $identifierType)
    {
        return $query->where('identifier', $identifier)
            ->where('identifier_type', $identifierType);
    }

    public function scopeForAction($query, $action)
    {
        return $query->where('action', $action);
    }
}
