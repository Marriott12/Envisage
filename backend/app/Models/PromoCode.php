<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class PromoCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'description',
        'type',
        'value',
        'minimum_order_amount',
        'maximum_discount',
        'usage_limit',
        'usage_count',
        'per_user_limit',
        'starts_at',
        'expires_at',
        'is_active',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'minimum_order_amount' => 'decimal:2',
        'maximum_discount' => 'decimal:2',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function usages()
    {
        return $this->hasMany(PromoCodeUsage::class);
    }

    // Check if promo code is valid
    public function isValid($userId = null, $orderAmount = 0)
    {
        // Check if active
        if (!$this->is_active) {
            return ['valid' => false, 'message' => 'This promo code is not active.'];
        }

        // Check start date
        if ($this->starts_at && Carbon::now()->lt($this->starts_at)) {
            return ['valid' => false, 'message' => 'This promo code is not yet active.'];
        }

        // Check expiration
        if ($this->expires_at && Carbon::now()->gt($this->expires_at)) {
            return ['valid' => false, 'message' => 'This promo code has expired.'];
        }

        // Check usage limit
        if ($this->usage_limit && $this->usage_count >= $this->usage_limit) {
            return ['valid' => false, 'message' => 'This promo code has reached its usage limit.'];
        }

        // Check minimum order amount
        if ($orderAmount < $this->minimum_order_amount) {
            return [
                'valid' => false,
                'message' => "Minimum order amount of $" . $this->minimum_order_amount . " required."
            ];
        }

        // Check per-user limit
        if ($userId) {
            $userUsageCount = $this->usages()->where('user_id', $userId)->count();
            if ($userUsageCount >= $this->per_user_limit) {
                return ['valid' => false, 'message' => 'You have already used this promo code.'];
            }
        }

        return ['valid' => true, 'message' => 'Promo code is valid.'];
    }

    // Calculate discount amount
    public function calculateDiscount($orderAmount)
    {
        $discount = 0;

        if ($this->type === 'percentage') {
            $discount = ($orderAmount * $this->value) / 100;
        } else {
            $discount = $this->value;
        }

        // Apply maximum discount cap
        if ($this->maximum_discount && $discount > $this->maximum_discount) {
            $discount = $this->maximum_discount;
        }

        // Discount cannot exceed order amount
        if ($discount > $orderAmount) {
            $discount = $orderAmount;
        }

        return round($discount, 2);
    }

    // Increment usage count
    public function incrementUsage($userId = null, $orderId = null, $discountAmount = 0)
    {
        $this->increment('usage_count');

        PromoCodeUsage::create([
            'promo_code_id' => $this->id,
            'user_id' => $userId,
            'order_id' => $orderId,
            'discount_amount' => $discountAmount,
        ]);
    }
}
