<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PreorderNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'pre_order_id',
        'type',
        'message',
        'sent',
        'sent_at',
    ];

    protected $casts = [
        'sent' => 'boolean',
        'sent_at' => 'datetime',
    ];

    /**
     * Get the pre-order this notification belongs to
     */
    public function preOrder()
    {
        return $this->belongsTo(PreOrder::class);
    }

    /**
     * Mark notification as sent
     */
    public function markAsSent()
    {
        $this->update([
            'sent' => true,
            'sent_at' => now(),
        ]);
    }
}
