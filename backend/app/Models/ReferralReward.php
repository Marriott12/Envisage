<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReferralReward extends Model
{
    use HasFactory;

    protected $fillable = [
        'referrer_id',
        'referral_id',
        'reward_type',
        'amount',
        'currency',
        'status',
        'order_id',
        'description',
        'earned_at',
        'approved_at',
        'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'earned_at' => 'datetime',
        'approved_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    // Reward types
    const TYPE_COMMISSION = 'commission';
    const TYPE_BONUS = 'bonus';
    const TYPE_CREDIT = 'credit';

    // Statuses
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_PAID = 'paid';
    const STATUS_CANCELLED = 'cancelled';

    // Relationships
    public function referrer()
    {
        return $this->belongsTo(User::class, 'referrer_id');
    }

    public function referral()
    {
        return $this->belongsTo(Referral::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopePaid($query)
    {
        return $query->where('status', self::STATUS_PAID);
    }

    public function scopeCommission($query)
    {
        return $query->where('reward_type', self::TYPE_COMMISSION);
    }

    public function scopeBonus($query)
    {
        return $query->where('reward_type', self::TYPE_BONUS);
    }

    // Helper methods
    public static function createCommission($referrerId, $referralId, $orderId, $amount, $rate)
    {
        return self::create([
            'referrer_id' => $referrerId,
            'referral_id' => $referralId,
            'reward_type' => self::TYPE_COMMISSION,
            'amount' => $amount,
            'order_id' => $orderId,
            'status' => self::STATUS_PENDING,
            'description' => "Commission ({$rate}%) from referral purchase",
            'earned_at' => now(),
        ]);
    }

    public static function createBonus($referrerId, $referralId, $amount, $description)
    {
        return self::create([
            'referrer_id' => $referrerId,
            'referral_id' => $referralId,
            'reward_type' => self::TYPE_BONUS,
            'amount' => $amount,
            'status' => self::STATUS_PENDING,
            'description' => $description,
            'earned_at' => now(),
        ]);
    }

    public function approve()
    {
        $this->update([
            'status' => self::STATUS_APPROVED,
            'approved_at' => now(),
        ]);
    }

    public function markAsPaid()
    {
        $this->update([
            'status' => self::STATUS_PAID,
            'paid_at' => now(),
        ]);
    }

    public function cancel()
    {
        $this->update(['status' => self::STATUS_CANCELLED]);
    }
}
