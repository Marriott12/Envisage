<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'user_id',
        'order_id',
        'rating',
        'review',
        'title',
        'comment',
        'images',
        'verified_purchase',
        'helpful_count',
        'not_helpful_count',
    ];

    protected $casts = [
        'verified_purchase' => 'boolean',
        'images' => 'array',
        'rating' => 'integer',
        'helpful_count' => 'integer',
        'not_helpful_count' => 'integer',
    ];

    protected $appends = ['user_name', 'user_avatar'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function helpfulness()
    {
        return $this->hasMany(ReviewHelpfulness::class);
    }

    public function getUserNameAttribute()
    {
        return $this->user ? $this->user->name : 'Anonymous';
    }

    public function getUserAvatarAttribute()
    {
        return $this->user && $this->user->avatar 
            ? $this->user->avatar 
            : null;
    }
}
