<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_order_id',
        'product_id',
        'quantity',
        'unit_cost',
        'received_quantity',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_cost' => 'decimal:2',
        'received_quantity' => 'integer',
    ];

    /**
     * Get the purchase order this item belongs to
     */
    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    /**
     * Get the product
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get total cost for this item
     */
    public function getTotalCostAttribute()
    {
        return $this->quantity * $this->unit_cost;
    }

    /**
     * Get remaining quantity to receive
     */
    public function getRemainingQuantityAttribute()
    {
        return $this->quantity - $this->received_quantity;
    }

    /**
     * Check if fully received
     */
    public function isFullyReceived()
    {
        return $this->received_quantity >= $this->quantity;
    }

    /**
     * Get receive percentage
     */
    public function getReceivePercentageAttribute()
    {
        if ($this->quantity == 0) {
            return 0;
        }
        return round(($this->received_quantity / $this->quantity) * 100, 1);
    }
}
