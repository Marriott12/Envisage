<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Refund extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id', 'return_request_id', 'user_id', 'amount',
        'payment_method', 'transaction_id', 'status', 'notes', 'processed_at'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'processed_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function returnRequest()
    {
        return $this->belongsTo(ReturnRequest::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
