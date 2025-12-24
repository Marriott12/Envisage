<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Services\CurrencyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CurrencyController extends Controller
{
    protected $currencyService;

    public function __construct(CurrencyService $currencyService)
    {
        $this->currencyService = $currencyService;
    }

    /**
     * Get all active currencies
     * GET /api/currencies
     */
    public function index()
    {
        $currencies = $this->currencyService->getActiveCurrencies();

        return response()->json([
            'success' => true,
            'currencies' => $currencies,
        ]);
    }

    /**
     * Convert amount between currencies
     * POST /api/currencies/convert
     */
    public function convert(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0',
            'from' => 'required|string|size:3',
            'to' => 'required|string|size:3',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $result = $this->currencyService->convert(
                $request->amount,
                strtoupper($request->from),
                strtoupper($request->to),
                $request->user()->id ?? null
            );

            return response()->json([
                'success' => true,
                'conversion' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get exchange rates
     * GET /api/currencies/rates
     */
    public function getRates(Request $request)
    {
        $baseCurrency = $request->get('base', 'USD');
        $targetCurrencies = $request->get('currencies', ['EUR', 'GBP', 'JPY']);

        if (!is_array($targetCurrencies)) {
            $targetCurrencies = explode(',', $targetCurrencies);
        }

        $rates = [];
        foreach ($targetCurrencies as $currency) {
            $rate = $this->currencyService->getExchangeRate($baseCurrency, $currency);
            if ($rate) {
                $rates[$currency] = $rate;
            }
        }

        return response()->json([
            'success' => true,
            'base' => $baseCurrency,
            'rates' => $rates,
            'updated_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * Get user's preferred currency
     * GET /api/currencies/user-preference
     */
    public function getUserPreference(Request $request)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required'
            ], 401);
        }

        return response()->json([
            'success' => true,
            'currency' => $user->preferred_currency ?? 'USD',
        ]);
    }

    /**
     * Set user's preferred currency
     * PUT /api/currencies/user-preference
     */
    public function setUserPreference(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'currency' => 'required|string|size:3',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required'
            ], 401);
        }

        $result = $this->currencyService->setUserCurrency(
            $user->id,
            strtoupper($request->currency)
        );

        if ($result) {
            return response()->json([
                'success' => true,
                'message' => 'Currency preference updated',
                'currency' => strtoupper($request->currency),
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Invalid currency code or currency not active'
        ], 400);
    }

    /**
     * Update exchange rates from API (Admin only)
     * POST /api/currencies/update-rates
     */
    public function updateRates(Request $request)
    {
        $baseCurrency = $request->get('base', 'USD');

        $result = $this->currencyService->updateExchangeRates($baseCurrency);

        if ($result) {
            return response()->json([
                'success' => true,
                'message' => 'Exchange rates updated successfully',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to update exchange rates'
        ], 500);
    }

    /**
     * Format amount in currency
     * POST /api/currencies/format
     */
    public function formatAmount(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric',
            'currency' => 'required|string|size:3',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $formatted = $this->currencyService->formatAmount(
            $request->amount,
            strtoupper($request->currency)
        );

        return response()->json([
            'success' => true,
            'formatted' => $formatted,
            'amount' => $request->amount,
            'currency' => strtoupper($request->currency),
        ]);
    }
}
