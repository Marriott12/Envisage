<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RewardsCatalog extends Model
{
    use HasFactory;

    protected $table = 'rewards_catalog';

    protected $fillable = [
        'name', 'description', 'image', 'points_cost', 'type',
        'value', 'stock_quantity', 'is_active'
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function redemptions()
    {
        return $this->hasMany(RewardRedemption::class, 'reward_id');
    }

    public function isAvailable()
    {
        return $this->is_active && 
               ($this->stock_quantity === null || $this->stock_quantity > 0);
    }
}
