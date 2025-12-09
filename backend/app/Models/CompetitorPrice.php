<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompetitorPrice extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id', 'competitor_name', 'competitor_url', 'price',
        'currency', 'last_checked_at'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'last_checked_at' => 'datetime',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
