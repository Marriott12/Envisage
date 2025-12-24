<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'symbol',
        'rate',
        'is_active',
        'is_base',
        'decimal_places',
        'format',
    ];

    protected $casts = [
        'rate' => 'decimal:6',
        'is_active' => 'boolean',
        'is_base' => 'boolean',
        'decimal_places' => 'integer',
    ];

    /**
     * Format amount in this currency
     */
    public function formatAmount($amount)
    {
        $formatted = number_format($amount, $this->decimal_places);
        
        return str_replace(
            ['{symbol}', '{amount}', '{code}'],
            [$this->symbol, $formatted, $this->code],
            $this->format
        );
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeBase($query)
    {
        return $query->where('is_base', true);
    }
}
