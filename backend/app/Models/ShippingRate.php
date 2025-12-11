<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShippingRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'carrier',
        'service_code',
        'service_name',
        'base_rate',
        'per_kg_rate',
        'min_weight',
        'max_weight',
        'min_delivery_days',
        'max_delivery_days',
        'from_country',
        'to_country',
        'is_active',
    ];

    protected $casts = [
        'base_rate' => 'decimal:2',
        'per_kg_rate' => 'decimal:2',
        'min_weight' => 'decimal:2',
        'max_weight' => 'decimal:2',
        'min_delivery_days' => 'integer',
        'max_delivery_days' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Calculate shipping cost for given weight
     */
    public function calculateCost($weight)
    {
        if ($weight < $this->min_weight || ($this->max_weight && $weight > $this->max_weight)) {
            return null;
        }

        return $this->base_rate + ($weight * $this->per_kg_rate);
    }

    /**
     * Get estimated delivery range
     */
    public function getDeliveryEstimate()
    {
        if ($this->min_delivery_days === $this->max_delivery_days) {
            return "{$this->min_delivery_days} days";
        }

        return "{$this->min_delivery_days}-{$this->max_delivery_days} days";
    }
}
