<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BnplInstallment extends Model
{
    use HasFactory;

    protected $fillable = [
        'bnpl_order_id',
        'installment_number',
        'amount',
        'due_date',
        'paid_date',
        'status',
        'payment_method',
        'transaction_id',
    ];

    protected $casts = [
        'installment_number' => 'integer',
        'amount' => 'decimal:2',
        'due_date' => 'date',
        'paid_date' => 'datetime',
    ];

    /**
     * Get the BNPL order this installment belongs to
     */
    public function bnplOrder()
    {
        return $this->belongsTo(BnplOrder::class);
    }

    /**
     * Check if installment is paid
     */
    public function isPaid()
    {
        return $this->status === 'paid';
    }

    /**
     * Check if installment is overdue
     */
    public function isOverdue()
    {
        return $this->status === 'pending' && now()->greaterThan($this->due_date);
    }

    /**
     * Mark as paid
     */
    public function markAsPaid($paymentMethod, $transactionId)
    {
        $this->update([
            'status' => 'paid',
            'paid_date' => now(),
            'payment_method' => $paymentMethod,
            'transaction_id' => $transactionId,
        ]);
    }
}
