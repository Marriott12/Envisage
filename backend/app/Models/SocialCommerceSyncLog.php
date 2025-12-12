<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class SocialCommerceSyncLog extends Model
{
    use HasFactory;

    const PLATFORM_INSTAGRAM = 'instagram';
    const PLATFORM_FACEBOOK = 'facebook';
    const PLATFORM_TIKTOK = 'tiktok';

    const SYNC_TYPE_PRODUCTS = 'products';
    const SYNC_TYPE_INVENTORY = 'inventory';
    const SYNC_TYPE_ORDERS = 'orders';
    const SYNC_TYPE_CATALOG = 'catalog';

    const DIRECTION_EXPORT = 'export';
    const DIRECTION_IMPORT = 'import';

    const STATUS_PENDING = 'pending';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';

    protected $fillable = [
        'platform',
        'sync_type',
        'direction',
        'status',
        'items_total',
        'items_processed',
        'items_successful',
        'items_failed',
        'error_message',
        'summary',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'items_total' => 'integer',
        'items_processed' => 'integer',
        'items_successful' => 'integer',
        'items_failed' => 'integer',
        'summary' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Scope: By platform
     */
    public function scopePlatform($query, $platform)
    {
        return $query->where('platform', $platform);
    }

    /**
     * Scope: By sync type
     */
    public function scopeSyncType($query, $type)
    {
        return $query->where('sync_type', $type);
    }

    /**
     * Scope: By direction
     */
    public function scopeDirection($query, $direction)
    {
        return $query->where('direction', $direction);
    }

    /**
     * Scope: By status
     */
    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: Completed
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope: Failed
     */
    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /**
     * Scope: Recent syncs
     */
    public function scopeRecent($query, $days = 7)
    {
        return $query->where('created_at', '>=', Carbon::now()->subDays($days));
    }

    /**
     * Start sync
     */
    public function start()
    {
        $this->status = self::STATUS_IN_PROGRESS;
        $this->started_at = Carbon::now();
        $this->save();
    }

    /**
     * Complete sync
     */
    public function complete($summary = null)
    {
        $this->status = self::STATUS_COMPLETED;
        $this->completed_at = Carbon::now();
        
        if ($summary) {
            $this->summary = $summary;
        }

        $this->save();
    }

    /**
     * Mark as failed
     */
    public function fail($errorMessage)
    {
        $this->status = self::STATUS_FAILED;
        $this->error_message = $errorMessage;
        $this->completed_at = Carbon::now();
        $this->save();
    }

    /**
     * Update progress
     */
    public function updateProgress($processed, $successful, $failed)
    {
        $this->items_processed = $processed;
        $this->items_successful = $successful;
        $this->items_failed = $failed;
        $this->save();
    }

    /**
     * Get duration in seconds
     */
    public function getDurationAttribute()
    {
        if (!$this->started_at || !$this->completed_at) {
            return null;
        }

        return $this->started_at->diffInSeconds($this->completed_at);
    }

    /**
     * Get success rate percentage
     */
    public function getSuccessRateAttribute()
    {
        if ($this->items_total == 0) {
            return 0;
        }

        return round(($this->items_successful / $this->items_total) * 100, 2);
    }

    /**
     * Check if successful
     */
    public function isSuccessful()
    {
        return $this->status === self::STATUS_COMPLETED &&
               $this->items_failed == 0;
    }

    /**
     * Check if partially successful
     */
    public function isPartiallySuccessful()
    {
        return $this->status === self::STATUS_COMPLETED &&
               $this->items_successful > 0 &&
               $this->items_failed > 0;
    }

    /**
     * Get statistics
     */
    public static function getStatistics($platform = null, $days = 30)
    {
        $query = static::recent($days);

        if ($platform) {
            $query->platform($platform);
        }

        $total = $query->count();
        $completed = $query->completed()->count();
        $failed = $query->failed()->count();

        return [
            'total_syncs' => $total,
            'completed' => $completed,
            'failed' => $failed,
            'success_rate' => $total > 0 ? round(($completed / $total) * 100, 2) : 0,
            'by_platform' => static::recent($days)
                ->groupBy('platform')
                ->selectRaw('platform, COUNT(*) as count, 
                            SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed')
                ->get()
                ->mapWithKeys(function($item) {
                    return [$item->platform => [
                        'total' => $item->count,
                        'completed' => $item->completed,
                    ]];
                }),
            'by_type' => static::recent($days)
                ->groupBy('sync_type')
                ->selectRaw('sync_type, COUNT(*) as count')
                ->pluck('count', 'sync_type'),
            'by_direction' => static::recent($days)
                ->groupBy('direction')
                ->selectRaw('direction, COUNT(*) as count')
                ->pluck('count', 'direction'),
            'avg_duration_seconds' => $query->completed()
                ->whereNotNull('started_at')
                ->whereNotNull('completed_at')
                ->get()
                ->avg(function($log) {
                    return $log->duration;
                }),
            'total_items_synced' => $query->completed()->sum('items_successful'),
        ];
    }

    /**
     * Get latest sync for platform and type
     */
    public static function getLatestSync($platform, $syncType, $direction = null)
    {
        $query = static::platform($platform)
            ->syncType($syncType);

        if ($direction) {
            $query->direction($direction);
        }

        return $query->orderBy('created_at', 'desc')->first();
    }

    /**
     * Check if sync is due
     */
    public static function isSyncDue($platform, $syncType, $hoursInterval = 24)
    {
        $lastSync = static::getLatestSync($platform, $syncType);

        if (!$lastSync) {
            return true;
        }

        return $lastSync->completed_at <= Carbon::now()->subHours($hoursInterval);
    }
}
