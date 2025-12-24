<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RecommendationClick extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'product_id',
        'algorithm',
        'confidence_score',
        'position',
        'clicked',
        'purchased',
        'clicked_at',
    ];

    protected $casts = [
        'confidence_score' => 'decimal:4',
        'clicked' => 'boolean',
        'purchased' => 'boolean',
        'clicked_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function scopeByAlgorithm($query, $algorithm)
    {
        return $query->where('algorithm', $algorithm);
    }

    public function scopeClicked($query)
    {
        return $query->where('clicked', true);
    }

    public function scopePurchased($query)
    {
        return $query->where('purchased', true);
    }
}
