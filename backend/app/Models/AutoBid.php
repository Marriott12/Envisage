<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AutoBid extends Model
{
    use HasFactory;

    protected $fillable = [
        'auction_id',
        'user_id',
        'max_amount',
        'increment',
        'is_active',
    ];

    protected $casts = [
        'max_amount' => 'decimal:2',
        'increment' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function auction()
    {
        return $this->belongsTo(Auction::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
