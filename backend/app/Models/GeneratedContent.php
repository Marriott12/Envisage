<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GeneratedContent extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'content_type',
        'tone',
        'length',
        'prompt',
        'generated_text',
        'tokens_used',
        'cost_usd',
        'approved',
        'used',
    ];

    protected $casts = [
        'cost_usd' => 'decimal:4',
        'approved' => 'boolean',
        'used' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('content_type', $type);
    }

    public function scopeApproved($query)
    {
        return $query->where('approved', true);
    }

    public function scopeUsed($query)
    {
        return $query->where('used', true);
    }
}
