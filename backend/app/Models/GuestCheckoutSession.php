<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GuestCheckoutSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_token',
        'email',
        'name',
        'phone',
        'shipping_address',
        'cart_data',
        'expires_at',
    ];

    protected $casts = [
        'shipping_address' => 'array',
        'cart_data' => 'array',
        'expires_at' => 'datetime',
    ];
}
