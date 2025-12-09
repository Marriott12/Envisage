<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductQuestion extends Model
{
    use HasFactory;

    protected $fillable = ['product_id', 'user_id', 'question', 'upvotes', 'is_answered'];

    protected $casts = ['is_answered' => 'boolean'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function answers()
    {
        return $this->hasMany(ProductAnswer::class, 'question_id');
    }

    public function upvotes()
    {
        return $this->hasMany(QuestionUpvote::class, 'question_id');
    }

    public function hasUpvoted($userId)
    {
        return $this->upvotes()->where('user_id', $userId)->exists();
    }
}
