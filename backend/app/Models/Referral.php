<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Referral extends Model
{
    use HasFactory;

    protected $fillable = [
        'referrer_id', 'referred_id', 'referral_code', 'email',
        'status', 'points_earned', 'registered_at', 'completed_at'
    ];

    protected $casts = [
        'registered_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function referrer()
    {
        return $this->belongsTo(User::class, 'referrer_id');
    }

    public function referred()
    {
        return $this->belongsTo(User::class, 'referred_id');
    }

    public static function generateUniqueCode()
    {
        do {
            $code = strtoupper(substr(md5(uniqid(rand(), true)), 0, 8));
        } while (self::where('referral_code', $code)->exists());

        return $code;
    }
}
