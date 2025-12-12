<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Referral extends Model
{
    use HasFactory;

    protected $fillable = [
        'referrer_id',
        'referee_id',
        'referee_email',
        'referee_name',
        'status',
        'registered_at',
        'converted_at',
        'expires_at',
        'referral_code',
        'source',
        'metadata',
        // Legacy fields
        'referred_id',
        'email',
        'points_earned',
        'completed_at'
    ];

    protected $casts = [
        'registered_at' => 'datetime',
        'converted_at' => 'datetime',
        'expires_at' => 'datetime',
        'completed_at' => 'datetime',
        'metadata' => 'array',
    ];

    // Statuses
    const STATUS_PENDING = 'pending';
    const STATUS_REGISTERED = 'registered';
    const STATUS_CONVERTED = 'converted';
    const STATUS_EXPIRED = 'expired';

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($referral) {
            if (!$referral->referral_code) {
                $referral->referral_code = self::generateUniqueCode();
            }
            if (!$referral->expires_at) {
                $referral->expires_at = now()->addDays(90);
            }
        });
    }

    // Relationships
    public function referrer()
    {
        return $this->belongsTo(User::class, 'referrer_id');
    }

    public function referee()
    {
        return $this->belongsTo(User::class, 'referee_id');
    }

    // Legacy relationship
    public function referred()
    {
        return $this->belongsTo(User::class, 'referred_id');
    }

    public function rewards()
    {
        return $this->hasMany(ReferralReward::class);
    }

    public function conversions()
    {
        return $this->hasMany(ReferralConversion::class);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeRegistered($query)
    {
        return $query->where('status', self::STATUS_REGISTERED);
    }

    public function scopeConverted($query)
    {
        return $query->where('status', self::STATUS_CONVERTED);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', [self::STATUS_PENDING, self::STATUS_REGISTERED])
            ->where('expires_at', '>', now());
    }

    public function scopeExpired($query)
    {
        return $query->where(function ($q) {
            $q->where('status', self::STATUS_EXPIRED)
              ->orWhere('expires_at', '<=', now());
        });
    }

    // Helper methods
    public static function generateUniqueCode($length = 10)
    {
        do {
            $code = strtoupper(Str::random($length));
        } while (self::where('referral_code', $code)->exists());

        return $code;
    }

    public function markAsRegistered($userId)
    {
        $this->update([
            'referee_id' => $userId,
            'status' => self::STATUS_REGISTERED,
            'registered_at' => now(),
        ]);
    }

    public function markAsConverted()
    {
        $this->update([
            'status' => self::STATUS_CONVERTED,
            'converted_at' => now(),
        ]);
    }

    public function isExpired()
    {
        return $this->expires_at < now();
    }

    public function daysToConvert()
    {
        if (!$this->converted_at || !$this->registered_at) {
            return null;
        }

        return $this->registered_at->diffInDays($this->converted_at);
    }
}
