<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class SocialCommerceOrder extends Model
{
    use HasFactory;

    const PLATFORM_INSTAGRAM = 'instagram';
    const PLATFORM_FACEBOOK = 'facebook';
    const PLATFORM_TIKTOK = 'tiktok';

    const STATUS_PENDING = 'pending';
    const STATUS_IMPORTED = 'imported';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_FAILED = 'failed';

    protected $fillable = [
        'order_id',
        'platform',
        'platform_order_id',
        'customer_name',
        'customer_email',
        'customer_phone',
        'shipping_address',
        'total_amount',
        'currency',
        'status',
        'platform_data',
        'platform_created_at',
        'imported_at',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'platform_data' => 'array',
        'platform_created_at' => 'datetime',
        'imported_at' => 'datetime',
    ];

    /**
     * Get the imported order
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
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
     * Scope: Pending import
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope: Successfully imported
     */
    public function scopeImported($query)
    {
        return $query->whereIn('status', [self::STATUS_IMPORTED, self::STATUS_COMPLETED]);
    }

    /**
     * Scope: Recent orders
     */
    public function scopeRecent($query, $days = 30)
    {
        return $query->where('platform_created_at', '>=', Carbon::now()->subDays($days));
    }

    /**
     * Mark as imported
     */
    public function markImported($orderId)
    {
        $this->order_id = $orderId;
        $this->status = self::STATUS_IMPORTED;
        $this->imported_at = Carbon::now();
        $this->save();
    }

    /**
     * Mark as completed
     */
    public function markCompleted()
    {
        $this->status = self::STATUS_COMPLETED;
        $this->save();
    }

    /**
     * Mark as failed
     */
    public function markFailed()
    {
        $this->status = self::STATUS_FAILED;
        $this->save();
    }

    /**
     * Check if already imported
     */
    public function isImported()
    {
        return $this->order_id !== null;
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

        return [
            'total_orders' => $query->count(),
            'pending' => $query->pending()->count(),
            'imported' => $query->imported()->count(),
            'failed' => $query->status(self::STATUS_FAILED)->count(),
            'total_revenue' => $query->imported()->sum('total_amount'),
            'by_platform' => static::recent($days)
                ->groupBy('platform')
                ->selectRaw('platform, COUNT(*) as count, SUM(total_amount) as revenue')
                ->get()
                ->mapWithKeys(function($item) {
                    return [$item->platform => [
                        'count' => $item->count,
                        'revenue' => $item->revenue,
                    ]];
                }),
            'by_status' => static::recent($days)
                ->groupBy('status')
                ->selectRaw('status, COUNT(*) as count')
                ->pluck('count', 'status'),
            'avg_order_value' => $query->imported()->avg('total_amount'),
        ];
    }

    /**
     * Parse platform data to extract items
     */
    public function getItemsFromPlatformData()
    {
        if (!$this->platform_data) {
            return [];
        }

        // Platform-specific parsing
        switch ($this->platform) {
            case self::PLATFORM_INSTAGRAM:
            case self::PLATFORM_FACEBOOK:
                return $this->platform_data['items'] ?? [];
                
            case self::PLATFORM_TIKTOK:
                return $this->platform_data['products'] ?? [];
                
            default:
                return [];
        }
    }

    /**
     * Get platform display name
     */
    public function getPlatformNameAttribute()
    {
        return ucfirst($this->platform);
    }

    /**
     * Get platform order URL
     */
    public function getPlatformOrderUrlAttribute()
    {
        switch ($this->platform) {
            case self::PLATFORM_INSTAGRAM:
                return "https://business.instagram.com/orders/{$this->platform_order_id}";
            case self::PLATFORM_FACEBOOK:
                return "https://business.facebook.com/orders/{$this->platform_order_id}";
            case self::PLATFORM_TIKTOK:
                return "https://seller.tiktokshop.com/order/{$this->platform_order_id}";
            default:
                return null;
        }
    }
}
