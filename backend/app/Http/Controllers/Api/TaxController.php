<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TaxRule;
use App\Models\TaxExemption;
use App\Services\TaxService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TaxController extends Controller
{
    protected $taxService;

    public function __construct(TaxService $taxService)
    {
        $this->taxService = $taxService;
    }

    /**
     * Calculate tax for given parameters
     * POST /api/taxes/calculate
     */
    public function calculate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'country' => 'required|string|size:2',
            'state' => 'nullable|string',
            'city' => 'nullable|string',
            'zip_code' => 'nullable|string',
            'items' => 'required|array',
            'items.*.product_id' => 'nullable|integer',
            'items.*.category_id' => 'nullable|integer',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.quantity' => 'required|integer|min:1',
            'shipping' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $params = [
            'country' => $request->country,
            'state' => $request->state,
            'city' => $request->city,
            'zip_code' => $request->zip_code,
            'user_id' => $request->user()->id ?? null,
            'items' => $request->items,
            'shipping' => $request->shipping ?? 0,
        ];

        $result = $this->taxService->calculateTax($params);

        return response()->json([
            'success' => true,
            'tax_calculation' => $result,
        ]);
    }

    /**
     * Get tax rates for location
     * GET /api/taxes/rates
     */
    public function getRates(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'country' => 'required|string|size:2',
            'state' => 'nullable|string',
            'city' => 'nullable|string',
            'zip_code' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $rates = $this->taxService->getTaxRates(
            $request->country,
            $request->state,
            $request->city,
            $request->zip_code
        );

        return response()->json([
            'success' => true,
            'rates' => $rates,
        ]);
    }

    /**
     * Estimate tax for simple calculation
     * POST /api/taxes/estimate
     */
    public function estimate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0',
            'country' => 'required|string|size:2',
            'state' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $estimate = $this->taxService->estimateTax(
            $request->amount,
            $request->country,
            $request->state
        );

        return response()->json([
            'success' => true,
            'estimate' => $estimate,
        ]);
    }

    /**
     * Get all tax rules (Admin only)
     * GET /api/taxes/rules
     */
    public function getRules(Request $request)
    {
        $query = TaxRule::query();

        if ($request->has('country')) {
            $query->where('country', $request->country);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $rules = $query->orderBy('priority', 'desc')
            ->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'rules' => $rules,
        ]);
    }

    /**
     * Create tax rule (Admin only)
     * POST /api/taxes/rules
     */
    public function createRule(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'country' => 'required|string|size:2',
            'state' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            'zip_code' => 'nullable|string|max:20',
            'type' => 'required|in:sales_tax,vat,gst,custom',
            'rate' => 'required|numeric|min:0|max:1',
            'is_compound' => 'boolean',
            'priority' => 'integer|min:0',
            'valid_from' => 'nullable|date',
            'valid_until' => 'nullable|date|after:valid_from',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $rule = TaxRule::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Tax rule created successfully',
            'rule' => $rule,
        ], 201);
    }

    /**
     * Update tax rule (Admin only)
     * PUT /api/taxes/rules/{id}
     */
    public function updateRule(Request $request, $id)
    {
        $rule = TaxRule::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'string|max:255',
            'rate' => 'numeric|min:0|max:1',
            'is_active' => 'boolean',
            'priority' => 'integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $rule->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Tax rule updated successfully',
            'rule' => $rule,
        ]);
    }

    /**
     * Delete tax rule (Admin only)
     * DELETE /api/taxes/rules/{id}
     */
    public function deleteRule($id)
    {
        $rule = TaxRule::findOrFail($id);
        $rule->delete();

        return response()->json([
            'success' => true,
            'message' => 'Tax rule deleted successfully',
        ]);
    }

    /**
     * Get user's tax exemptions
     * GET /api/taxes/exemptions
     */
    public function getExemptions(Request $request)
    {
        $user = $request->user();
        
        $exemptions = TaxExemption::where('user_id', $user->id)
            ->orWhere('type', 'product')
            ->orWhere('type', 'category')
            ->active()
            ->get();

        return response()->json([
            'success' => true,
            'exemptions' => $exemptions,
        ]);
    }

    /**
     * Validate tax ID
     * POST /api/taxes/validate-id
     */
    public function validateTaxId(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tax_id' => 'required|string',
            'country' => 'required|string|size:2',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $isValid = $this->taxService->validateTaxId(
            $request->tax_id,
            $request->country
        );

        return response()->json([
            'success' => true,
            'valid' => $isValid,
            'tax_id' => $request->tax_id,
        ]);
    }
}
