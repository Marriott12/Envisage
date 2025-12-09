<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Dispute extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'user_id',
        'type',
        'status',
        'amount',
        'reason',
        'description',
        'evidence',
        'admin_response',
        'resolved_at'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'evidence' => 'array',
        'resolved_at' => 'datetime',
    ];

    /**
     * Get the order that owns the dispute
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the user that owns the dispute
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to get pending disputes
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope to get resolved disputes
     */
    public function scopeResolved($query)
    {
        return $query->where('status', 'resolved');
    }
}
