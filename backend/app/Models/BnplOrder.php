<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BnplOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'user_id',
        'bnpl_plan_id',
        'total_amount',
        'down_payment',
        'remaining_amount',
        'installments_count',
        'installments_paid',
        'status',
        'first_payment_date',
        'next_payment_date',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'down_payment' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
        'installments_count' => 'integer',
        'installments_paid' => 'integer',
        'first_payment_date' => 'date',
        'next_payment_date' => 'date',
    ];

    /**
     * Get the order associated with this BNPL order
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the user who made this BNPL order
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the BNPL plan used
     */
    public function bnplPlan()
    {
        return $this->belongsTo(BnplPlan::class);
    }

    /**
     * Get all installments for this order
     */
    public function installments()
    {
        return $this->hasMany(BnplInstallment::class);
    }

    /**
     * Check if order is active
     */
    public function isActive()
    {
        return $this->status === 'active';
    }

    /**
     * Check if order is completed
     */
    public function isCompleted()
    {
        return $this->status === 'completed' || $this->installments_paid >= $this->installments_count;
    }

    /**
     * Get progress percentage
     */
    public function getProgressPercentage()
    {
        if ($this->installments_count === 0) {
            return 0;
        }
        return round(($this->installments_paid / $this->installments_count) * 100, 2);
    }
}
