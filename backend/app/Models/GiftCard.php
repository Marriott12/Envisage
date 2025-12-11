<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GiftCard extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'initial_amount',
        'balance',
        'purchased_by',
        'used_by',
        'recipient_email',
        'message',
        'expires_at',
        'status',
    ];

    protected $casts = [
        'initial_amount' => 'decimal:2',
        'balance' => 'decimal:2',
        'expires_at' => 'date',
    ];

    public function purchaser()
    {
        return $this->belongsTo(User::class, 'purchased_by');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'used_by');
    }

    public function isValid()
    {
        return $this->status === 'active' && 
               $this->balance > 0 && 
               (!$this->expires_at || $this->expires_at->isFuture());
    }
}
