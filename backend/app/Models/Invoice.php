<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Invoice extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'invoice_number',
        'order_id',
        'user_id',
        'seller_id',
        'subtotal',
        'tax_amount',
        'shipping_amount',
        'discount_amount',
        'total_amount',
        'tax_breakdown',
        'billing_name',
        'billing_address',
        'billing_city',
        'billing_state',
        'billing_country',
        'billing_zip',
        'billing_email',
        'billing_phone',
        'tax_id',
        'company_name',
        'currency',
        'pdf_path',
        'status',
        'paid_amount',
        'paid_at',
        'due_date',
        'notes',
        'terms',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'shipping_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'tax_breakdown' => 'array',
        'paid_at' => 'datetime',
        'due_date' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    /**
     * Generate unique invoice number
     */
    public static function generateInvoiceNumber()
    {
        $prefix = 'INV';
        $date = Carbon::now()->format('Ymd');
        
        $lastInvoice = static::whereRaw("invoice_number LIKE '{$prefix}-{$date}-%'")
            ->orderBy('invoice_number', 'desc')
            ->first();

        if ($lastInvoice) {
            $lastNumber = intval(substr($lastInvoice->invoice_number, -4));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . '-' . $date . '-' . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Check if invoice is overdue
     */
    public function isOverdue()
    {
        if (!$this->due_date || in_array($this->status, ['paid', 'cancelled', 'refunded'])) {
            return false;
        }

        return Carbon::now()->isAfter($this->due_date) && $this->paid_amount < $this->total_amount;
    }

    /**
     * Get balance due
     */
    public function getBalanceDue()
    {
        return $this->total_amount - $this->paid_amount;
    }

    /**
     * Mark as paid
     */
    public function markAsPaid($amount = null)
    {
        $amount = $amount ?? $this->total_amount;
        
        $this->update([
            'paid_amount' => $this->paid_amount + $amount,
            'paid_at' => now(),
            'status' => ($this->paid_amount + $amount >= $this->total_amount) ? 'paid' : 'partially_paid'
        ]);
    }

    /**
     * Get formatted invoice number
     */
    public function getFormattedInvoiceNumberAttribute()
    {
        return $this->invoice_number;
    }

    /**
     * Scopes
     */
    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', Carbon::now())
            ->whereIn('status', ['issued', 'partially_paid'])
            ->where(function($q) {
                $q->whereColumn('paid_amount', '<', 'total_amount')
                  ->orWhereNull('paid_amount');
            });
    }

    public function scopeUnpaid($query)
    {
        return $query->whereIn('status', ['issued', 'partially_paid'])
            ->where(function($q) {
                $q->whereColumn('paid_amount', '<', 'total_amount')
                  ->orWhereNull('paid_amount');
            });
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }
}
