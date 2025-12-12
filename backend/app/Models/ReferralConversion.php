<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReferralConversion extends Model
{
    use HasFactory;

    protected $fillable = [
        'referral_id',
        'order_id',
        'order_amount',
        'commission_amount',
        'commission_rate',
        'is_first_purchase',
        'days_to_convert',
    ];

    protected $casts = [
        'order_amount' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'commission_rate' => 'decimal:2',
        'is_first_purchase' => 'boolean',
    ];

    // Relationships
    public function referral()
    {
        return $this->belongsTo(Referral::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    // Helper methods
    public static function recordConversion($referralId, $orderId, $orderAmount, $commissionRate, $isFirstPurchase = false)
    {
        $commissionAmount = ($orderAmount * $commissionRate) / 100;

        $referral = Referral::find($referralId);
        $daysToConvert = null;

        if ($referral && $referral->registered_at) {
            $daysToConvert = $referral->registered_at->diffInDays(now());
        }

        return self::create([
            'referral_id' => $referralId,
            'order_id' => $orderId,
            'order_amount' => $orderAmount,
            'commission_amount' => $commissionAmount,
            'commission_rate' => $commissionRate,
            'is_first_purchase' => $isFirstPurchase,
            'days_to_convert' => $daysToConvert,
        ]);
    }
}
