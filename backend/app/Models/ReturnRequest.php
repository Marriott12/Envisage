<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReturnRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id', 'order_item_id', 'user_id', 'reason', 'description',
        'images', 'status', 'tracking_number', 'refund_amount', 
        'refund_status', 'approved_at', 'completed_at'
    ];

    protected $casts = [
        'images' => 'array',
        'refund_amount' => 'decimal:2',
        'approved_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function refund()
    {
        return $this->hasOne(Refund::class);
    }
}
