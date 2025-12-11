<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Voucher extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'description',
        'type',
        'value',
        'min_purchase_amount',
        'max_discount',
        'usage_limit',
        'usage_count',
        'per_user_limit',
        'starts_at',
        'expires_at',
        'is_active',
        'applicable_products',
        'applicable_categories',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'min_purchase_amount' => 'decimal:2',
        'max_discount' => 'decimal:2',
        'usage_count' => 'integer',
        'usage_limit' => 'integer',
        'per_user_limit' => 'integer',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
        'applicable_products' => 'array',
        'applicable_categories' => 'array',
    ];

    public function usages()
    {
        return $this->hasMany(VoucherUsage::class);
    }

    public function isValid()
    {
        $now = now();
        
        return $this->is_active &&
               (!$this->starts_at || $this->starts_at->isPast()) &&
               (!$this->expires_at || $this->expires_at->isFuture()) &&
               (!$this->usage_limit || $this->usage_count < $this->usage_limit);
    }

    public function canBeUsedBy($userId)
    {
        if (!$this->per_user_limit) {
            return true;
        }

        $userUsageCount = $this->usages()->where('user_id', $userId)->count();
        return $userUsageCount < $this->per_user_limit;
    }

    public function calculateDiscount($orderAmount)
    {
        if ($this->min_purchase_amount && $orderAmount < $this->min_purchase_amount) {
            return 0;
        }

        $discount = 0;

        switch ($this->type) {
            case 'percentage':
                $discount = $orderAmount * ($this->value / 100);
                break;
            case 'fixed':
                $discount = $this->value;
                break;
            case 'free_shipping':
                return 'free_shipping';
        }

        if ($this->max_discount && $discount > $this->max_discount) {
            $discount = $this->max_discount;
        }

        return min($discount, $orderAmount);
    }
}
