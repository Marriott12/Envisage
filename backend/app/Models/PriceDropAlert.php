<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PriceDropAlert extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'product_id',
        'target_price',
        'original_price',
        'notified',
        'notified_at',
        'is_active',
    ];

    protected $casts = [
        'target_price' => 'decimal:2',
        'original_price' => 'decimal:2',
        'notified' => 'boolean',
        'notified_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function shouldNotify($currentPrice)
    {
        return $this->is_active && 
               !$this->notified && 
               $currentPrice <= $this->target_price;
    }

    public function markAsNotified()
    {
        $this->update([
            'notified' => true,
            'notified_at' => now(),
            'is_active' => false,
        ]);
    }
}
