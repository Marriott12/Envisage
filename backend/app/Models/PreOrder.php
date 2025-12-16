<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PreOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'user_id',
        'quantity',
        'price_per_unit',
        'total_amount',
        'deposit_paid',
        'deposit_amount',
        'status',
        'expected_ship_date',
        'order_id',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'price_per_unit' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'deposit_paid' => 'boolean',
        'deposit_amount' => 'decimal:2',
        'expected_ship_date' => 'date',
    ];

    /**
     * Get the product for this pre-order
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the user who made this pre-order
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the order if converted
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get notifications for this pre-order
     */
    public function notifications()
    {
        return $this->hasMany(PreorderNotification::class);
    }

    /**
     * Check if pre-order is active
     */
    public function isActive()
    {
        return $this->status === 'reserved';
    }

    /**
     * Check if pre-order is shipped
     */
    public function isShipped()
    {
        return $this->status === 'shipped';
    }

    /**
     * Cancel the pre-order
     */
    public function cancel()
    {
        $this->update(['status' => 'cancelled']);
    }

    /**
     * Convert to actual order
     */
    public function convertToOrder()
    {
        $this->update(['status' => 'charged']);
    }
}
