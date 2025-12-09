<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AbandonedCart extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'session_id', 'email', 'cart_data', 'cart_total',
        'is_recovered', 'recovered_at', 'recovery_email_sent',
        'last_email_sent_at', 'recovery_token', 'abandoned_at'
    ];

    protected $casts = [
        'cart_data' => 'array',
        'cart_total' => 'decimal:2',
        'is_recovered' => 'boolean',
        'recovered_at' => 'datetime',
        'last_email_sent_at' => 'datetime',
        'abandoned_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function recoveryEmails()
    {
        return $this->hasMany(CartRecoveryEmail::class);
    }

    public function generateRecoveryToken()
    {
        $this->recovery_token = bin2hex(random_bytes(32));
        $this->save();
        return $this->recovery_token;
    }
}
