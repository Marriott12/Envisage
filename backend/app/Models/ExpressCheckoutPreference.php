<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExpressCheckoutPreference extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'enabled',
        'default_payment_method_id',
        'default_shipping_address_id',
        'default_billing_address_id',
        'skip_review_step',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'skip_review_step' => 'boolean',
    ];

    /**
     * Get the user that owns the preferences.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the default payment method.
     */
    public function defaultPaymentMethod()
    {
        return $this->belongsTo(SavedPaymentMethod::class, 'default_payment_method_id');
    }

    /**
     * Get the default shipping address.
     */
    public function defaultShippingAddress()
    {
        return $this->belongsTo(SavedAddress::class, 'default_shipping_address_id');
    }

    /**
     * Get the default billing address.
     */
    public function defaultBillingAddress()
    {
        return $this->belongsTo(SavedAddress::class, 'default_billing_address_id');
    }

    /**
     * Check if express checkout is fully configured
     */
    public function isFullyConfigured()
    {
        return $this->enabled 
            && $this->default_payment_method_id 
            && $this->default_shipping_address_id 
            && $this->default_billing_address_id;
    }
}
