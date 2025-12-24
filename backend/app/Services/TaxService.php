<?php

namespace App\Services;

use App\Models\TaxRule;
use App\Models\TaxExemption;
use App\Models\TaxCalculation;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Collection;

class TaxService
{
    /**
     * Calculate tax for an order
     */
    public function calculateTax($params)
    {
        $country = $params['country'] ?? 'US';
        $state = $params['state'] ?? null;
        $city = $params['city'] ?? null;
        $zipCode = $params['zip_code'] ?? null;
        $userId = $params['user_id'] ?? null;
        $items = $params['items'] ?? [];
        $shipping = $params['shipping'] ?? 0;

        // Get applicable tax rules
        $taxRules = TaxRule::active()
            ->forLocation($country, $state, $city, $zipCode)
            ->orderBy('priority', 'desc')
            ->get();

        if ($taxRules->isEmpty()) {
            return [
                'total_tax' => 0,
                'tax_breakdown' => [],
                'subtotal' => $this->calculateSubtotal($items),
                'shipping' => $shipping,
            ];
        }

        // Check for user exemptions
        $userExemptions = $userId ? 
            TaxExemption::active()->forUser($userId)->get() : 
            collect([]);

        $taxBreakdown = [];
        $totalTax = 0;
        $subtotal = $this->calculateSubtotal($items);
        $exemptionsApplied = [];

        foreach ($items as $item) {
            $productId = $item['product_id'] ?? null;
            $categoryId = $item['category_id'] ?? null;
            $lineTotal = $item['price'] * $item['quantity'];

            // Check product/category exemptions
            $isExempt = false;
            
            if ($productId) {
                $productExemption = TaxExemption::active()->forProduct($productId)->first();
                if ($productExemption && $productExemption->isValid()) {
                    $isExempt = true;
                    $exemptionsApplied[] = [
                        'type' => 'product',
                        'id' => $productId,
                        'reason' => $productExemption->reason,
                    ];
                }
            }

            if (!$isExempt && $categoryId) {
                $categoryExemption = TaxExemption::active()->forCategory($categoryId)->first();
                if ($categoryExemption && $categoryExemption->isValid()) {
                    $isExempt = true;
                    $exemptionsApplied[] = [
                        'type' => 'category',
                        'id' => $categoryId,
                        'reason' => $categoryExemption->reason,
                    ];
                }
            }

            if (!$isExempt && !$userExemptions->isEmpty()) {
                $isExempt = true;
                $exemptionsApplied[] = [
                    'type' => 'user',
                    'reason' => $userExemptions->first()->reason,
                ];
            }

            if (!$isExempt) {
                foreach ($taxRules as $rule) {
                    if ($categoryId && !$rule->appliesToCategory($categoryId)) {
                        continue;
                    }

                    $taxAmount = $lineTotal * $rule->rate;
                    $totalTax += $taxAmount;

                    $taxBreakdown[] = [
                        'name' => $rule->name,
                        'type' => $rule->type,
                        'rate' => $rule->rate,
                        'amount' => round($taxAmount, 2),
                        'taxable_amount' => $lineTotal,
                    ];
                }
            }
        }

        // Tax on shipping (if applicable)
        $shippingTaxRules = $taxRules->where('applicable_categories', null);
        foreach ($shippingTaxRules as $rule) {
            if ($shipping > 0) {
                $shippingTax = $shipping * $rule->rate;
                $totalTax += $shippingTax;

                $taxBreakdown[] = [
                    'name' => $rule->name . ' (Shipping)',
                    'type' => $rule->type,
                    'rate' => $rule->rate,
                    'amount' => round($shippingTax, 2),
                    'taxable_amount' => $shipping,
                ];
            }
        }

        return [
            'total_tax' => round($totalTax, 2),
            'tax_breakdown' => $this->consolidateTaxBreakdown($taxBreakdown),
            'exemptions_applied' => $exemptionsApplied,
            'subtotal' => $subtotal,
            'shipping' => $shipping,
            'grand_total' => round($subtotal + $shipping + $totalTax, 2),
        ];
    }

    /**
     * Save tax calculation for order
     */
    public function saveTaxCalculation(Order $order, array $taxData)
    {
        return TaxCalculation::create([
            'order_id' => $order->id,
            'user_id' => $order->user_id,
            'country' => $taxData['country'] ?? 'US',
            'state' => $taxData['state'] ?? null,
            'city' => $taxData['city'] ?? null,
            'zip_code' => $taxData['zip_code'] ?? null,
            'subtotal' => $taxData['subtotal'] ?? 0,
            'shipping' => $taxData['shipping'] ?? 0,
            'total_tax' => $taxData['total_tax'] ?? 0,
            'tax_breakdown' => $taxData['tax_breakdown'] ?? [],
            'exemptions_applied' => $taxData['exemptions_applied'] ?? [],
        ]);
    }

    /**
     * Calculate subtotal from items
     */
    private function calculateSubtotal(array $items)
    {
        $subtotal = 0;
        foreach ($items as $item) {
            $subtotal += ($item['price'] ?? 0) * ($item['quantity'] ?? 1);
        }
        return $subtotal;
    }

    /**
     * Consolidate tax breakdown by name
     */
    private function consolidateTaxBreakdown(array $breakdown)
    {
        $consolidated = [];
        
        foreach ($breakdown as $tax) {
            $name = $tax['name'];
            if (isset($consolidated[$name])) {
                $consolidated[$name]['amount'] += $tax['amount'];
                $consolidated[$name]['taxable_amount'] += $tax['taxable_amount'];
            } else {
                $consolidated[$name] = $tax;
            }
        }

        return array_values($consolidated);
    }

    /**
     * Get tax rates for a location
     */
    public function getTaxRates($country, $state = null, $city = null, $zipCode = null)
    {
        $rules = TaxRule::active()
            ->forLocation($country, $state, $city, $zipCode)
            ->get();

        return $rules->map(function($rule) {
            return [
                'id' => $rule->id,
                'name' => $rule->name,
                'type' => $rule->type,
                'rate' => $rule->rate,
                'rate_percentage' => $rule->rate * 100,
                'country' => $rule->country,
                'state' => $rule->state,
                'city' => $rule->city,
                'zip_code' => $rule->zip_code,
            ];
        });
    }

    /**
     * Estimate tax for quick calculation
     */
    public function estimateTax($amount, $country, $state = null)
    {
        $rules = TaxRule::active()
            ->forLocation($country, $state)
            ->get();

        $totalRate = $rules->sum('rate');
        
        return [
            'amount' => $amount,
            'tax_rate' => $totalRate,
            'tax_amount' => round($amount * $totalRate, 2),
            'total' => round($amount * (1 + $totalRate), 2),
        ];
    }

    /**
     * Validate tax ID (basic validation)
     */
    public function validateTaxId($taxId, $country)
    {
        // Basic format validation
        // In production, integrate with tax ID validation APIs
        
        if (empty($taxId)) {
            return false;
        }

        switch ($country) {
            case 'US':
                // EIN format: XX-XXXXXXX
                return preg_match('/^\d{2}-\d{7}$/', $taxId);
            
            case 'GB':
                // UK VAT: GB followed by 9 or 12 digits
                return preg_match('/^GB\d{9}(\d{3})?$/', $taxId);
            
            case 'DE':
                // German VAT: DE followed by 9 digits
                return preg_match('/^DE\d{9}$/', $taxId);
            
            default:
                return strlen($taxId) > 5;
        }
    }
}
