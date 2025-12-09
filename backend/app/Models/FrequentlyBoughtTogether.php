<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FrequentlyBoughtTogether extends Model
{
    use HasFactory;

    protected $table = 'frequently_bought_together';

    protected $fillable = [
        'product_id', 'related_product_id', 'times_bought_together',
        'confidence_score'
    ];

    protected $casts = [
        'confidence_score' => 'decimal:4',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function relatedProduct()
    {
        return $this->belongsTo(Product::class, 'related_product_id');
    }
}
