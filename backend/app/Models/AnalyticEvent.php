<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnalyticEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'session_id',
        'event_type',
        'event_category',
        'event_action',
        'event_label',
        'properties',
        'page_url',
        'referrer',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'device_type',
        'browser',
        'os',
        'country',
        'city',
        'ip_address',
        'revenue',
    ];

    protected $casts = [
        'properties' => 'array',
        'revenue' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function session()
    {
        return $this->belongsTo(UserSession::class, 'session_id', 'session_id');
    }

    // Scopes
    public function scopePageViews($query)
    {
        return $query->where('event_type', 'page_view');
    }

    public function scopeProductViews($query)
    {
        return $query->where('event_type', 'product_view');
    }

    public function scopeAddToCart($query)
    {
        return $query->where('event_type', 'add_to_cart');
    }

    public function scopePurchases($query)
    {
        return $query->where('event_type', 'purchase');
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeBySession($query, $sessionId)
    {
        return $query->where('session_id', $sessionId);
    }

    public function scopeByDevice($query, $deviceType)
    {
        return $query->where('device_type', $deviceType);
    }

    public function scopeByCountry($query, $country)
    {
        return $query->where('country', $country);
    }

    // Helper Methods
    public static function trackEvent($data)
    {
        return self::create($data);
    }

    public static function trackPageView($sessionId, $userId = null, $data = [])
    {
        return self::create(array_merge([
            'session_id' => $sessionId,
            'user_id' => $userId,
            'event_type' => 'page_view',
            'event_category' => 'navigation',
        ], $data));
    }

    public static function trackProductView($sessionId, $productId, $userId = null, $data = [])
    {
        return self::create(array_merge([
            'session_id' => $sessionId,
            'user_id' => $userId,
            'event_type' => 'product_view',
            'event_category' => 'product',
            'properties' => ['product_id' => $productId],
        ], $data));
    }

    public static function trackPurchase($sessionId, $userId, $revenue, $data = [])
    {
        return self::create(array_merge([
            'session_id' => $sessionId,
            'user_id' => $userId,
            'event_type' => 'purchase',
            'event_category' => 'ecommerce',
            'revenue' => $revenue,
        ], $data));
    }
}
