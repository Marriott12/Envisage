<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class SocialCommerceProduct extends Model
{
    use HasFactory;

    const PLATFORM_INSTAGRAM = 'instagram';
    const PLATFORM_FACEBOOK = 'facebook';
    const PLATFORM_TIKTOK = 'tiktok';

    const STATUS_PENDING = 'pending';
    const STATUS_ACTIVE = 'active';
    const STATUS_REJECTED = 'rejected';
    const STATUS_REMOVED = 'removed';

    protected $fillable = [
        'product_id',
        'platform',
        'platform_product_id',
        'status',
        'rejection_reason',
        'synced_at',
        'last_updated_at',
        'platform_data',
    ];

    protected $casts = [
        'synced_at' => 'datetime',
        'last_updated_at' => 'datetime',
        'platform_data' => 'array',
    ];

    /**
     * Get the product
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Scope: By platform
     */
    public function scopePlatform($query, $platform)
    {
        return $query->where('platform', $platform);
    }

    /**
     * Scope: By status
     */
    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: Active products
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope: Pending products
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope: Needs update (changed in last sync)
     */
    public function scopeNeedsUpdate($query, $hours = 24)
    {
        return $query->active()
            ->where(function($q) use ($hours) {
                $q->whereNull('last_updated_at')
                  ->orWhere('last_updated_at', '<=', Carbon::now()->subHours($hours));
            });
    }

    /**
     * Mark as synced
     */
    public function markSynced($platformProductId = null)
    {
        $this->synced_at = Carbon::now();
        $this->last_updated_at = Carbon::now();
        $this->status = self::STATUS_ACTIVE;
        
        if ($platformProductId) {
            $this->platform_product_id = $platformProductId;
        }

        $this->save();
    }

    /**
     * Mark as rejected
     */
    public function markRejected($reason)
    {
        $this->status = self::STATUS_REJECTED;
        $this->rejection_reason = $reason;
        $this->save();
    }

    /**
     * Mark as removed
     */
    public function markRemoved()
    {
        $this->status = self::STATUS_REMOVED;
        $this->save();
    }

    /**
     * Check if needs sync
     */
    public function needsSync()
    {
        return $this->status === self::STATUS_PENDING ||
               $this->last_updated_at === null ||
               $this->last_updated_at <= Carbon::now()->subHours(24);
    }

    /**
     * Get statistics
     */
    public static function getStatistics($platform = null)
    {
        $query = static::query();

        if ($platform) {
            $query->platform($platform);
        }

        return [
            'total' => $query->count(),
            'active' => $query->active()->count(),
            'pending' => $query->pending()->count(),
            'rejected' => $query->status(self::STATUS_REJECTED)->count(),
            'by_platform' => static::groupBy('platform')
                ->selectRaw('platform, COUNT(*) as count')
                ->pluck('count', 'platform'),
            'by_status' => static::groupBy('status')
                ->selectRaw('status, COUNT(*) as count')
                ->pluck('count', 'status'),
            'needs_update' => static::needsUpdate()->count(),
        ];
    }

    /**
     * Get platform display name
     */
    public function getPlatformNameAttribute()
    {
        return ucfirst($this->platform);
    }

    /**
     * Get product URL on platform
     */
    public function getPlatformUrlAttribute()
    {
        if (!$this->platform_product_id) {
            return null;
        }

        switch ($this->platform) {
            case self::PLATFORM_INSTAGRAM:
                return "https://www.instagram.com/shopping/product/{$this->platform_product_id}";
            case self::PLATFORM_FACEBOOK:
                return "https://www.facebook.com/marketplace/item/{$this->platform_product_id}";
            case self::PLATFORM_TIKTOK:
                return "https://www.tiktok.com/product/{$this->platform_product_id}";
            default:
                return null;
        }
    }
}
