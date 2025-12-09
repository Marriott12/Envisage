<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoyaltyTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'points', 'type', 'source', 'order_id',
        'referral_id', 'description', 'balance_after', 'expires_at'
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function referral()
    {
        return $this->belongsTo(User::class, 'referral_id');
    }
}
