<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductSimilarity extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'similar_product_id',
        'similarity_score',
        'similarity_type',
        'calculated_at',
    ];

    protected $casts = [
        'similarity_score' => 'decimal:4',
        'calculated_at' => 'datetime',
    ];

    // Relationships
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function similarProduct()
    {
        return $this->belongsTo(Product::class, 'similar_product_id');
    }

    // Scopes
    public function scopeCollaborative($query)
    {
        return $query->where('similarity_type', 'collaborative');
    }

    public function scopeContentBased($query)
    {
        return $query->where('similarity_type', 'content_based');
    }

    public function scopeHybrid($query)
    {
        return $query->where('similarity_type', 'hybrid');
    }

    public function scopeHighSimilarity($query, $threshold = 0.5)
    {
        return $query->where('similarity_score', '>=', $threshold);
    }

    // Helper methods
    public static function getSimilarProducts($productId, $limit = 10, $type = null)
    {
        $query = self::where('product_id', $productId)
            ->orderByDesc('similarity_score');

        if ($type) {
            $query->where('similarity_type', $type);
        }

        return $query->limit($limit)
            ->with('similarProduct')
            ->get()
            ->pluck('similarProduct');
    }

    public static function recordSimilarity($productId, $similarProductId, $score, $type)
    {
        return self::updateOrCreate(
            [
                'product_id' => $productId,
                'similar_product_id' => $similarProductId,
                'similarity_type' => $type,
            ],
            [
                'similarity_score' => $score,
                'calculated_at' => now(),
            ]
        );
    }
}
