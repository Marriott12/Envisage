<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoyaltyPoint extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'balance',
        'lifetime_earned',
        'lifetime_redeemed',
        'tier',
        'referral_code'
    ];

    protected $casts = [
        'balance' => 'integer',
        'lifetime_earned' => 'integer',
        'lifetime_redeemed' => 'integer',
    ];

    /**
     * Get the user that owns the loyalty points
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all transactions for this user
     */
    public function transactions()
    {
        return $this->hasMany(LoyaltyTransaction::class, 'user_id', 'user_id');
    }

    /**
     * Add points
     */
    public function addPoints($amount, $type, $source, $description = null)
    {
        $this->balance += $amount;
        $this->lifetime_earned += $amount;
        $this->save();

        return $this->transactions()->create([
            'points' => $amount,
            'type' => $type,
            'source' => $source,
            'description' => $description,
            'balance_after' => $this->balance,
        ]);
    }

    /**
     * Deduct points
     */
    public function deductPoints($amount, $type, $source, $description = null)
    {
        if ($this->balance < $amount) {
            throw new \Exception('Insufficient points balance');
        }

        $this->balance -= $amount;
        $this->lifetime_redeemed += $amount;
        $this->save();

        return $this->transactions()->create([
            'points' => -$amount,
            'type' => $type,
            'source' => $source,
            'description' => $description,
            'balance_after' => $this->balance,
        ]);
    }

    /**
     * Calculate tier based on lifetime earned points
     */
    public function calculateTier()
    {
        if ($this->lifetime_earned >= 50000) return 'diamond';
        if ($this->lifetime_earned >= 25000) return 'platinum';
        if ($this->lifetime_earned >= 10000) return 'gold';
        if ($this->lifetime_earned >= 5000) return 'silver';
        return 'bronze';
    }

    /**
     * Update tier
     */
    public function updateTier()
    {
        $this->tier = $this->calculateTier();
        $this->save();
    }
}
