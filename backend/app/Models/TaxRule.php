<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaxRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'country',
        'state',
        'city',
        'zip_code',
        'type',
        'rate',
        'is_compound',
        'priority',
        'is_active',
        'valid_from',
        'valid_until',
        'applicable_categories',
        'excluded_categories',
        'description',
    ];

    protected $casts = [
        'rate' => 'decimal:4',
        'is_compound' => 'boolean',
        'is_active' => 'boolean',
        'priority' => 'integer',
        'valid_from' => 'date',
        'valid_until' => 'date',
        'applicable_categories' => 'array',
        'excluded_categories' => 'array',
    ];

    /**
     * Check if rule is currently valid
     */
    public function isValid()
    {
        if (!$this->is_active) {
            return false;
        }

        $now = now();

        if ($this->valid_from && $now->isBefore($this->valid_from)) {
            return false;
        }

        if ($this->valid_until && $now->isAfter($this->valid_until)) {
            return false;
        }

        return true;
    }

    /**
     * Check if applies to location
     */
    public function appliesToLocation($country, $state = null, $city = null, $zipCode = null)
    {
        if ($this->country !== $country) {
            return false;
        }

        if ($this->state && $state && $this->state !== $state) {
            return false;
        }

        if ($this->city && $city && $this->city !== $city) {
            return false;
        }

        if ($this->zip_code && $zipCode && $this->zip_code !== $zipCode) {
            return false;
        }

        return true;
    }

    /**
     * Check if applies to category
     */
    public function appliesToCategory($categoryId)
    {
        if ($this->applicable_categories && !in_array($categoryId, $this->applicable_categories)) {
            return false;
        }

        if ($this->excluded_categories && in_array($categoryId, $this->excluded_categories)) {
            return false;
        }

        return true;
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function($q) {
                $q->whereNull('valid_from')
                  ->orWhere('valid_from', '<=', now());
            })
            ->where(function($q) {
                $q->whereNull('valid_until')
                  ->orWhere('valid_until', '>=', now());
            });
    }

    public function scopeForLocation($query, $country, $state = null, $city = null, $zipCode = null)
    {
        $query->where('country', $country);

        if ($state) {
            $query->where(function($q) use ($state) {
                $q->whereNull('state')
                  ->orWhere('state', $state);
            });
        }

        if ($city) {
            $query->where(function($q) use ($city) {
                $q->whereNull('city')
                  ->orWhere('city', $city);
            });
        }

        if ($zipCode) {
            $query->where(function($q) use ($zipCode) {
                $q->whereNull('zip_code')
                  ->orWhere('zip_code', $zipCode);
            });
        }

        return $query;
    }
}
