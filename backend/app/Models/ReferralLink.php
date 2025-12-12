<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ReferralLink extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'token',
        'url',
        'clicks',
        'conversions',
        'conversion_rate',
        'campaign_name',
        'utm_params',
        'is_active',
        'last_clicked_at',
    ];

    protected $casts = [
        'utm_params' => 'array',
        'is_active' => 'boolean',
        'conversion_rate' => 'decimal:2',
        'last_clicked_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($link) {
            if (!$link->token) {
                $link->token = self::generateUniqueToken();
            }
            if (!$link->url) {
                $link->url = self::buildUrl($link->token, $link->utm_params);
            }
        });
    }

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Helper methods
    public static function generateUniqueToken($length = 32)
    {
        do {
            $token = Str::random($length);
        } while (self::where('token', $token)->exists());

        return $token;
    }

    public static function buildUrl($token, $utmParams = [])
    {
        $baseUrl = config('app.url') . '/register?ref=' . $token;

        if (!empty($utmParams)) {
            $queryString = http_build_query($utmParams);
            $baseUrl .= '&' . $queryString;
        }

        return $baseUrl;
    }

    public function trackClick()
    {
        $this->increment('clicks');
        $this->update(['last_clicked_at' => now()]);
    }

    public function trackConversion()
    {
        $this->increment('conversions');
        $this->updateConversionRate();
    }

    public function updateConversionRate()
    {
        if ($this->clicks > 0) {
            $this->update([
                'conversion_rate' => round(($this->conversions / $this->clicks) * 100, 2),
            ]);
        }
    }
}
