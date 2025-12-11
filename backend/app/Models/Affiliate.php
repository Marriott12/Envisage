<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Affiliate extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'affiliate_code',
        'commission_rate',
        'total_earnings',
        'pending_earnings',
        'paid_earnings',
        'total_referrals',
        'total_sales',
        'status',
        'payment_method',
        'payment_details',
    ];

    protected $casts = [
        'commission_rate' => 'decimal:2',
        'total_earnings' => 'decimal:2',
        'pending_earnings' => 'decimal:2',
        'paid_earnings' => 'decimal:2',
        'total_referrals' => 'integer',
        'total_sales' => 'integer',
        'payment_details' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($affiliate) {
            if (!$affiliate->affiliate_code) {
                $affiliate->affiliate_code = strtoupper(substr(md5(uniqid()), 0, 10));
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function conversions()
    {
        return $this->hasMany(AffiliateConversion::class);
    }

    public function getConversionRateAttribute()
    {
        if ($this->total_referrals == 0) {
            return 0;
        }
        return ($this->total_sales / $this->total_referrals) * 100;
    }
}
