<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BnplPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'installments',
        'interval_days',
        'interest_rate',
        'minimum_amount',
        'maximum_amount',
        'active',
        'terms',
    ];

    protected $casts = [
        'installments' => 'integer',
        'interval_days' => 'integer',
        'interest_rate' => 'decimal:2',
        'minimum_amount' => 'decimal:2',
        'maximum_amount' => 'decimal:2',
        'active' => 'boolean',
    ];

    /**
     * Get BNPL orders using this plan
     */
    public function bnplOrders()
    {
        return $this->hasMany(BnplOrder::class);
    }

    /**
     * Scope to get only active plans
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Check if amount is within plan limits
     */
    public function isAmountValid($amount)
    {
        if ($amount < $this->minimum_amount) {
            return false;
        }

        if ($this->maximum_amount && $amount > $this->maximum_amount) {
            return false;
        }

        return true;
    }

    /**
     * Calculate installment amount
     */
    public function calculateInstallmentAmount($totalAmount, $downPayment = 0)
    {
        $remainingAmount = $totalAmount - $downPayment;
        $amountWithInterest = $remainingAmount * (1 + ($this->interest_rate / 100));
        return round($amountWithInterest / $this->installments, 2);
    }
}
