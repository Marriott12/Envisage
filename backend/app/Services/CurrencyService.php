<?php

namespace App\Services;

use App\Models\Currency;
use App\Models\ExchangeRate;
use App\Models\CurrencyConversion;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CurrencyService
{
    /**
     * Convert amount from one currency to another
     */
    public function convert($amount, $fromCurrency, $toCurrency, $userId = null)
    {
        // If same currency, return original amount
        if ($fromCurrency === $toCurrency) {
            return [
                'from_amount' => $amount,
                'to_amount' => $amount,
                'from_currency' => $fromCurrency,
                'to_currency' => $toCurrency,
                'rate' => 1.0,
            ];
        }

        // Get exchange rate
        $rate = $this->getExchangeRate($fromCurrency, $toCurrency);
        
        if (!$rate) {
            throw new \Exception("Exchange rate not available for {$fromCurrency} to {$toCurrency}");
        }

        $convertedAmount = round($amount * $rate, 2);

        // Log conversion
        CurrencyConversion::create([
            'user_id' => $userId,
            'from_currency' => $fromCurrency,
            'to_currency' => $toCurrency,
            'from_amount' => $amount,
            'to_amount' => $convertedAmount,
            'rate' => $rate,
        ]);

        return [
            'from_amount' => $amount,
            'to_amount' => $convertedAmount,
            'from_currency' => $fromCurrency,
            'to_currency' => $toCurrency,
            'rate' => $rate,
        ];
    }

    /**
     * Get exchange rate between two currencies
     */
    public function getExchangeRate($fromCurrency, $toCurrency)
    {
        // Check cache first
        $cacheKey = "exchange_rate_{$fromCurrency}_{$toCurrency}";
        
        return Cache::remember($cacheKey, 3600, function() use ($fromCurrency, $toCurrency) {
            // Check if we have a direct rate
            $directRate = ExchangeRate::where('from_currency', $fromCurrency)
                ->where('to_currency', $toCurrency)
                ->first();

            if ($directRate && $directRate->isRecent()) {
                return $directRate->rate;
            }

            // Try inverse rate
            $inverseRate = ExchangeRate::where('from_currency', $toCurrency)
                ->where('to_currency', $fromCurrency)
                ->first();

            if ($inverseRate && $inverseRate->isRecent()) {
                return $inverseRate->getInverseRate();
            }

            // Use base currency conversion
            $fromCurrency = Currency::where('code', $fromCurrency)->first();
            $toCurrency = Currency::where('code', $toCurrency)->first();

            if ($fromCurrency && $toCurrency) {
                // Convert to base then to target
                $baseCurrency = Currency::base()->first();
                if ($baseCurrency) {
                    $toBaseRate = $fromCurrency->rate;
                    $fromBaseRate = $toCurrency->rate;
                    return $fromBaseRate / $toBaseRate;
                }
            }

            return null;
        });
    }

    /**
     * Update exchange rates from external API
     */
    public function updateExchangeRates($baseCurrency = 'USD')
    {
        // Using exchangerate-api.com (free tier)
        $apiKey = config('services.exchange_rate.api_key');
        
        if (!$apiKey) {
            return false;
        }

        try {
            $response = Http::get("https://v6.exchangerate-api.com/v6/{$apiKey}/latest/{$baseCurrency}");
            
            if ($response->successful()) {
                $data = $response->json();
                $rates = $data['conversion_rates'] ?? [];

                foreach ($rates as $currency => $rate) {
                    ExchangeRate::updateOrCreate(
                        [
                            'from_currency' => $baseCurrency,
                            'to_currency' => $currency,
                        ],
                        [
                            'rate' => $rate,
                            'source' => 'api',
                            'updated_at' => now(),
                        ]
                    );
                }

                // Clear cache
                Cache::flush();

                return true;
            }
        } catch (\Exception $e) {
            \Log::error('Failed to update exchange rates: ' . $e->getMessage());
        }

        return false;
    }

    /**
     * Get all active currencies
     */
    public function getActiveCurrencies()
    {
        return Currency::active()->orderBy('code')->get();
    }

    /**
     * Get user's preferred currency
     */
    public function getUserCurrency($userId)
    {
        $user = \App\Models\User::find($userId);
        return $user ? $user->preferred_currency : 'USD';
    }

    /**
     * Set user's preferred currency
     */
    public function setUserCurrency($userId, $currencyCode)
    {
        $user = \App\Models\User::find($userId);
        
        if ($user) {
            $currency = Currency::where('code', $currencyCode)->active()->first();
            
            if ($currency) {
                $user->update(['preferred_currency' => $currencyCode]);
                return true;
            }
        }

        return false;
    }

    /**
     * Format amount in currency
     */
    public function formatAmount($amount, $currencyCode)
    {
        $currency = Currency::where('code', $currencyCode)->first();
        
        if (!$currency) {
            return $currencyCode . ' ' . number_format($amount, 2);
        }

        return $currency->formatAmount($amount);
    }

    /**
     * Get currency symbol
     */
    public function getCurrencySymbol($currencyCode)
    {
        $currency = Currency::where('code', $currencyCode)->first();
        return $currency ? $currency->symbol : $currencyCode;
    }

    /**
     * Convert product price to user's currency
     */
    public function convertProductPrice($product, $userCurrency)
    {
        $productCurrency = $product->currency ?? 'USD';
        $price = $product->price;

        if ($productCurrency === $userCurrency) {
            return $price;
        }

        $conversion = $this->convert($price, $productCurrency, $userCurrency);
        return $conversion['to_amount'];
    }
}
