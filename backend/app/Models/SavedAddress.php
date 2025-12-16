<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SavedAddress extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'label',
        'full_name',
        'phone',
        'address_line_1',
        'address_line_2',
        'city',
        'state',
        'postal_code',
        'country',
        'is_default_shipping',
        'is_default_billing',
    ];

    protected $casts = [
        'is_default_shipping' => 'boolean',
        'is_default_billing' => 'boolean',
    ];

    /**
     * Get the user that owns the address.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get formatted address
     */
    public function getFormattedAddressAttribute()
    {
        $lines = [
            $this->address_line_1,
            $this->address_line_2,
            $this->city . ', ' . $this->state . ' ' . $this->postal_code,
            $this->country,
        ];

        return implode("\n", array_filter($lines));
    }

    /**
     * Scope for shipping addresses
     */
    public function scopeShipping($query)
    {
        return $query->whereIn('type', ['shipping', 'both']);
    }

    /**
     * Scope for billing addresses
     */
    public function scopeBilling($query)
    {
        return $query->whereIn('type', ['billing', 'both']);
    }

    /**
     * Scope for default shipping
     */
    public function scopeDefaultShipping($query)
    {
        return $query->where('is_default_shipping', true);
    }

    /**
     * Scope for default billing
     */
    public function scopeDefaultBilling($query)
    {
        return $query->where('is_default_billing', true);
    }
}
