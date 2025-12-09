<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderDispute extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id', 'user_id', 'type', 'status', 'reason', 
        'evidence', 'resolved_by', 'resolution_notes', 'resolved_at'
    ];

    protected $casts = [
        'evidence' => 'array',
        'resolved_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function resolver()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}
