<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CartRecoveryEmail extends Model
{
    use HasFactory;

    protected $fillable = [
        'abandoned_cart_id', 'email', 'type', 'discount_amount',
        'discount_code', 'was_opened', 'was_clicked', 'sent_at',
        'opened_at', 'clicked_at'
    ];

    protected $casts = [
        'discount_amount' => 'decimal:2',
        'was_opened' => 'boolean',
        'was_clicked' => 'boolean',
        'sent_at' => 'datetime',
        'opened_at' => 'datetime',
        'clicked_at' => 'datetime',
    ];

    public function abandonedCart()
    {
        return $this->belongsTo(AbandonedCart::class);
    }
}
