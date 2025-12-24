<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaxCalculation extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'user_id',
        'country',
        'state',
        'city',
        'zip_code',
        'subtotal',
        'shipping',
        'total_tax',
        'tax_breakdown',
        'exemptions_applied',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'shipping' => 'decimal:2',
        'total_tax' => 'decimal:2',
        'tax_breakdown' => 'array',
        'exemptions_applied' => 'array',
    ];

    /**
     * Relationships
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
