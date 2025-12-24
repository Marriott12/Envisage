<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExchangeRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'from_currency',
        'to_currency',
        'rate',
        'source',
    ];

    protected $casts = [
        'rate' => 'decimal:6',
    ];

    /**
     * Get the inverse rate
     */
    public function getInverseRate()
    {
        return 1 / $this->rate;
    }

    /**
     * Check if rate is recent (within 24 hours)
     */
    public function isRecent()
    {
        return $this->updated_at->isAfter(now()->subHours(24));
    }
}
