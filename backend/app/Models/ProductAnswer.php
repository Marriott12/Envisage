<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductAnswer extends Model
{
    use HasFactory;

    protected $fillable = ['question_id', 'user_id', 'answer', 'is_seller', 'is_helpful', 'helpful_count'];

    protected $casts = [
        'is_seller' => 'boolean',
        'is_helpful' => 'boolean',
    ];

    public function question()
    {
        return $this->belongsTo(ProductQuestion::class, 'question_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
