<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SavedPaymentMethod extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'provider',
        'provider_payment_method_id',
        'last_four',
        'card_brand',
        'card_holder_name',
        'expiry_month',
        'expiry_year',
        'billing_address_id',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    protected $hidden = [
        'provider_payment_method_id',
    ];

    /**
     * Get the user that owns the payment method.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the billing address.
     */
    public function billingAddress()
    {
        return $this->belongsTo(SavedAddress::class, 'billing_address_id');
    }

    /**
     * Check if card is expired
     */
    public function isExpired()
    {
        if (!$this->expiry_month || !$this->expiry_year) {
            return false;
        }

        $expiryDate = \Carbon\Carbon::createFromDate($this->expiry_year, $this->expiry_month, 1)->endOfMonth();
        return $expiryDate->isPast();
    }

    /**
     * Get masked card number
     */
    public function getMaskedNumberAttribute()
    {
        return '**** **** **** ' . $this->last_four;
    }

    /**
     * Scope for default payment method
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Scope for active (non-expired) cards
     */
    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->where('type', '!=', 'card')
              ->orWhere(function ($q2) {
                  $q2->where('expiry_year', '>', now()->year)
                     ->orWhere(function ($q3) {
                         $q3->where('expiry_year', now()->year)
                            ->where('expiry_month', '>=', now()->month);
                     });
              });
        });
    }
}
