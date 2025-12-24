<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FraudAlert extends Model
{
    protected $fillable = [
        'user_id',
        'order_id',
        'risk_score',
        'risk_level',
        'details',
        'reasons',
        'status',
        'reviewed_at',
        'reviewed_by',
        'action_taken',
    ];

    protected $casts = [
        'details' => 'array',
        'reasons' => 'array',
        'risk_score' => 'decimal:4',
        'reviewed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
