<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LowStockAlert extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id', 'threshold_quantity', 'current_quantity',
        'is_active', 'last_alerted_at'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_alerted_at' => 'datetime',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function shouldAlert()
    {
        if (!$this->is_active) return false;
        if ($this->current_quantity > $this->threshold_quantity) return false;
        
        // Don't alert more than once per day
        if ($this->last_alerted_at && $this->last_alerted_at->isToday()) {
            return false;
        }

        return true;
    }
}
