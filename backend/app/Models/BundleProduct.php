<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BundleProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'bundle_id', 'product_id', 'quantity', 'price_at_time'
    ];

    protected $casts = [
        'price_at_time' => 'decimal:2',
    ];

    public function bundle()
    {
        return $this->belongsTo(ProductBundle::class, 'bundle_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
