<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserLoyaltyPoint extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'total_points', 'lifetime_points', 'tier', 'tier_achieved_at'
    ];

    protected $casts = [
        'tier_achieved_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transactions()
    {
        return $this->hasMany(LoyaltyTransaction::class, 'user_id', 'user_id');
    }

    public function addPoints($points, $source, $description = null, $orderId = null)
    {
        $this->total_points += $points;
        $this->lifetime_points += $points;
        $this->checkAndUpdateTier();
        $this->save();

        LoyaltyTransaction::create([
            'user_id' => $this->user_id,
            'points' => $points,
            'type' => 'earned',
            'source' => $source,
            'order_id' => $orderId,
            'description' => $description,
            'balance_after' => $this->total_points,
        ]);
    }

    public function deductPoints($points, $source, $description = null)
    {
        if ($this->total_points < $points) {
            return false;
        }

        $this->total_points -= $points;
        $this->save();

        LoyaltyTransaction::create([
            'user_id' => $this->user_id,
            'points' => -$points,
            'type' => 'redeemed',
            'source' => $source,
            'description' => $description,
            'balance_after' => $this->total_points,
        ]);

        return true;
    }

    protected function checkAndUpdateTier()
    {
        $tiers = [
            'diamond' => 10000,
            'platinum' => 5000,
            'gold' => 2000,
            'silver' => 500,
            'bronze' => 0,
        ];

        foreach ($tiers as $tier => $threshold) {
            if ($this->lifetime_points >= $threshold) {
                if ($this->tier !== $tier) {
                    $this->tier = $tier;
                    $this->tier_achieved_at = now();
                }
                break;
            }
        }
    }
}
