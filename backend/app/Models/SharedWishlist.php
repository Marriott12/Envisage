<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SharedWishlist extends Model
{
    use HasFactory;

    protected $fillable = [
        'wishlist_id',
        'user_id',
        'share_token',
        'privacy',
        'allow_comments',
        'views_count',
        'expires_at',
    ];

    protected $casts = [
        'allow_comments' => 'boolean',
        'views_count' => 'integer',
        'expires_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->share_token) {
                $model->share_token = Str::random(32);
            }
        });
    }

    public function wishlist()
    {
        return $this->belongsTo(Wishlist::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isExpired()
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function incrementViews()
    {
        $this->increment('views_count');
    }
}
