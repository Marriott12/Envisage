<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'provider',
        'provider_payment_method_id',
        'last_four',
        'brand',
        'exp_month',
        'exp_year',
        'holder_name',
        'email',
        'is_default',
        'metadata',
    ];

    protected $casts = [
        'exp_month' => 'integer',
        'exp_year' => 'integer',
        'is_default' => 'boolean',
        'metadata' => 'array',
    ];

    protected $hidden = [
        'provider_payment_method_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getDisplayNameAttribute()
    {
        if ($this->type === 'card') {
            return ucfirst($this->brand) . ' •••• ' . $this->last_four;
        } elseif ($this->type === 'paypal') {
            return 'PayPal - ' . $this->email;
        }
        return ucfirst($this->type);
    }

    public function getIsExpiredAttribute()
    {
        if ($this->type !== 'card') {
            return false;
        }
        
        $now = now();
        return $this->exp_year < $now->year || 
               ($this->exp_year == $now->year && $this->exp_month < $now->month);
    }
}
