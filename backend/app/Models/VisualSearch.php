<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VisualSearch extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'image_hash',
        'image_path',
        'results_count',
        'avg_similarity_score',
        'clicked_result',
        'processing_time_ms',
        'dominant_colors',
    ];

    protected $casts = [
        'avg_similarity_score' => 'decimal:4',
        'clicked_result' => 'boolean',
        'dominant_colors' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeWithResults($query)
    {
        return $query->where('results_count', '>', 0);
    }

    public function scopeClicked($query)
    {
        return $query->where('clicked_result', true);
    }
}
