<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RewardRedemption extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'reward_id', 'points_used', 'redemption_code',
        'is_used', 'used_at', 'expires_at'
    ];

    protected $casts = [
        'is_used' => 'boolean',
        'used_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reward()
    {
        return $this->belongsTo(RewardsCatalog::class, 'reward_id');
    }

    public static function generateRedemptionCode()
    {
        do {
            $code = 'REWARD-' . strtoupper(substr(md5(uniqid(rand(), true)), 0, 10));
        } while (self::where('redemption_code', $code)->exists());

        return $code;
    }
}
