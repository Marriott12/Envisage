<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\DynamicPricingService;
use App\Models\Product;
use App\Models\PriceRule;
use App\Models\PriceHistory;
use App\Models\CompetitorPrice;
use App\Models\DemandForecast;
use App\Models\PriceExperiment;
use App\Models\SurgePricingEvent;

class DynamicPricingController extends Controller
{
    protected $pricingService;

    public function __construct(DynamicPricingService $pricingService)
    {
        $this->pricingService = $pricingService;
    }

    /**
     * Get price recommendation for a product
     * GET /api/pricing/recommend/{productId}
     */
    public function getPriceRecommendation($productId)
    {
        $product = Product::find($productId);
        if (!$product) {
            return response()->json(['error' => 'Product not found'], 404);
        }

        $recommendation = $this->pricingService->calculateOptimalPrice($productId);

        return response()->json([
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'current_price' => $product->price,
            ],
            'recommendation' => $recommendation,
        ]);
    }

    /**
     * Apply price change to product
     * POST /api/pricing/apply
     */
    public function applyPriceChange(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'new_price' => 'required|numeric|min:0',
            'reason' => 'nullable|string|in:manual,rule_based,demand,competitor,surge',
            'notes' => 'nullable|string|max:500',
        ]);

        $success = $this->pricingService->applyPriceChange(
            $validated['product_id'],
            $validated['new_price'],
            $validated['reason'] ?? PriceHistory::REASON_MANUAL,
            null,
            auth()->id(),
            $validated['notes'] ?? null
        );

        if ($success) {
            return response()->json([
                'message' => 'Price updated successfully',
                'product_id' => $validated['product_id'],
                'new_price' => $validated['new_price'],
            ]);
        }

        return response()->json(['error' => 'Failed to update price'], 500);
    }

    /**
     * Get pricing rules
     * GET /api/pricing/rules
     */
    public function getRules(Request $request)
    {
        $query = PriceRule::with(['product:id,name', 'category:id,name'])
            ->byPriority();

        if ($request->has('product_id')) {
            $query->forProduct($request->product_id);
        }

        if ($request->has('category_id')) {
            $query->forCategory($request->category_id);
        }

        if ($request->has('type')) {
            $query->byType($request->type);
        }

        if ($request->boolean('active_only')) {
            $query->active();
        }

        $rules = $query->paginate($request->input('per_page', 20));

        return response()->json($rules);
    }

    /**
     * Create pricing rule
     * POST /api/pricing/rules
     */
    public function createRule(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'product_id' => 'nullable|exists:products,id',
            'category_id' => 'nullable|exists:categories,id',
            'rule_type' => 'required|in:demand_based,competitor_based,time_based,inventory_based',
            'min_price' => 'nullable|numeric|min:0',
            'max_price' => 'nullable|numeric|min:0',
            'target_margin' => 'nullable|numeric|min:0|max:100',
            'conditions' => 'nullable|array',
            'adjustments' => 'nullable|array',
            'priority' => 'required|integer|min:1|max:100',
            'is_active' => 'boolean',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after:starts_at',
        ]);

        $rule = PriceRule::create($validated);

        return response()->json([
            'message' => 'Pricing rule created successfully',
            'rule' => $rule->load(['product', 'category']),
        ], 201);
    }

    /**
     * Update pricing rule
     * PUT /api/pricing/rules/{id}
     */
    public function updateRule(Request $request, $id)
    {
        $rule = PriceRule::findOrFail($id);

        $validated = $request->validate([
            'name' => 'string|max:255',
            'product_id' => 'nullable|exists:products,id',
            'category_id' => 'nullable|exists:categories,id',
            'rule_type' => 'in:demand_based,competitor_based,time_based,inventory_based',
            'min_price' => 'nullable|numeric|min:0',
            'max_price' => 'nullable|numeric|min:0',
            'target_margin' => 'nullable|numeric|min:0|max:100',
            'conditions' => 'nullable|array',
            'adjustments' => 'nullable|array',
            'priority' => 'integer|min:1|max:100',
            'is_active' => 'boolean',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after:starts_at',
        ]);

        $rule->update($validated);

        return response()->json([
            'message' => 'Pricing rule updated successfully',
            'rule' => $rule->load(['product', 'category']),
        ]);
    }

    /**
     * Delete pricing rule
     * DELETE /api/pricing/rules/{id}
     */
    public function deleteRule($id)
    {
        $rule = PriceRule::findOrFail($id);
        $rule->delete();

        return response()->json(['message' => 'Pricing rule deleted successfully']);
    }

    /**
     * Get price history for product
     * GET /api/pricing/history/{productId}
     */
    public function getPriceHistory($productId, Request $request)
    {
        $product = Product::findOrFail($productId);

        $query = PriceHistory::forProduct($productId)
            ->with(['rule:id,name', 'user:id,name'])
            ->orderBy('changed_at', 'desc');

        if ($request->has('days')) {
            $query->recent($request->input('days'));
        }

        if ($request->has('reason')) {
            $query->byReason($request->reason);
        }

        $history = $query->paginate($request->input('per_page', 50));

        // Get stats
        $stats = PriceHistory::getProductStats($productId, $request->input('days', 30));
        $volatility = PriceHistory::getVolatilityScore($productId, $request->input('days', 30));

        return response()->json([
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'current_price' => $product->price,
            ],
            'history' => $history,
            'stats' => $stats,
            'volatility_score' => $volatility,
        ]);
    }

    /**
     * Get competitor prices for product
     * GET /api/pricing/competitors/{productId}
     */
    public function getCompetitorPrices($productId, Request $request)
    {
        $product = Product::findOrFail($productId);

        $query = CompetitorPrice::forProduct($productId)
            ->orderBy('scraped_at', 'desc');

        if ($request->boolean('in_stock_only')) {
            $query->inStock();
        }

        if ($request->boolean('high_quality_only')) {
            $query->highQuality();
        }

        if ($request->has('hours')) {
            $query->recent($request->input('hours'));
        }

        $competitors = $query->get();

        // Get competitive position
        $position = CompetitorPrice::getCompetitivePosition($productId);

        return response()->json([
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'current_price' => $product->price,
            ],
            'competitors' => $competitors,
            'competitive_position' => $position,
        ]);
    }

    /**
     * Get demand forecast for product
     * GET /api/pricing/forecast/{productId}
     */
    public function getDemandForecast($productId, Request $request)
    {
        $product = Product::findOrFail($productId);

        $days = $request->input('days', 7);
        
        // Generate forecasts if not exists
        $forecasts = $this->pricingService->generateDemandForecast($productId, $days);

        // Get forecast accuracy
        $accuracy = DemandForecast::getAccuracyStats($productId, 30);

        return response()->json([
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'current_price' => $product->price,
            ],
            'forecasts' => $forecasts,
            'accuracy_stats' => $accuracy,
        ]);
    }

    /**
     * Start price experiment (A/B test)
     * POST /api/pricing/experiments
     */
    public function startExperiment(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'name' => 'required|string|max:255',
            'variant_price' => 'required|numeric|min:0',
            'control_price' => 'nullable|numeric|min:0',
        ]);

        $result = $this->pricingService->startPriceExperiment(
            $validated['product_id'],
            $validated['name'],
            $validated['variant_price'],
            $validated['control_price'] ?? null
        );

        return response()->json($result, $result['success'] ? 201 : 400);
    }

    /**
     * Get experiment results
     * GET /api/pricing/experiments/{id}
     */
    public function getExperimentResults($id)
    {
        $experiment = PriceExperiment::findOrFail($id);
        $results = $experiment->getResults();

        return response()->json($results);
    }

    /**
     * List experiments
     * GET /api/pricing/experiments
     */
    public function listExperiments(Request $request)
    {
        $query = PriceExperiment::with('product:id,name');

        if ($request->has('product_id')) {
            $query->forProduct($request->product_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $experiments = $query->orderBy('started_at', 'desc')
            ->paginate($request->input('per_page', 20));

        return response()->json($experiments);
    }

    /**
     * Complete experiment
     * POST /api/pricing/experiments/{id}/complete
     */
    public function completeExperiment($id)
    {
        $experiment = PriceExperiment::findOrFail($id);
        $results = $experiment->completeExperiment();

        return response()->json([
            'message' => 'Experiment completed successfully',
            'results' => $results,
        ]);
    }

    /**
     * Activate surge pricing
     * POST /api/pricing/surge
     */
    public function activateSurgePricing(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'event_type' => 'required|in:flash_sale,holiday,stock_low,high_traffic',
            'surge_multiplier' => 'required|numeric|min:1|max:3',
            'duration_minutes' => 'nullable|integer|min:1|max:1440',
        ]);

        $surge = $this->pricingService->activateSurgePricing(
            $validated['product_id'],
            $validated['event_type'],
            $validated['surge_multiplier'],
            $validated['duration_minutes'] ?? 60
        );

        return response()->json([
            'message' => 'Surge pricing activated successfully',
            'surge' => $surge,
        ], 201);
    }

    /**
     * Get active surge pricing
     * GET /api/pricing/surge/{productId}
     */
    public function getSurgePricing($productId)
    {
        $product = Product::findOrFail($productId);
        $surgeSummary = SurgePricingEvent::getSurgeSummary($productId);

        return response()->json([
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
            ],
            'surge' => $surgeSummary,
        ]);
    }

    /**
     * Deactivate surge pricing
     * DELETE /api/pricing/surge/{productId}
     */
    public function deactivateSurgePricing($productId)
    {
        $product = Product::findOrFail($productId);
        
        $activeSurge = SurgePricingEvent::getActiveSurge($productId);
        if (!$activeSurge) {
            return response()->json(['error' => 'No active surge pricing found'], 404);
        }

        $activeSurge->deactivate();

        // Revert to optimal price
        $optimal = $this->pricingService->calculateOptimalPrice($productId);
        if ($optimal) {
            $this->pricingService->applyPriceChange(
                $productId,
                $optimal['recommended_price'],
                PriceHistory::REASON_MANUAL,
                null,
                auth()->id(),
                'Surge pricing deactivated'
            );
        }

        return response()->json(['message' => 'Surge pricing deactivated successfully']);
    }

    /**
     * Get pricing analytics dashboard
     * GET /api/pricing/analytics
     */
    public function getAnalytics(Request $request)
    {
        $days = $request->input('days', 30);
        $analytics = $this->pricingService->getPricingAnalytics($days);

        return response()->json($analytics);
    }

    /**
     * Bulk optimize prices
     * POST /api/pricing/bulk-optimize
     */
    public function bulkOptimize(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'nullable|exists:categories,id',
            'dry_run' => 'boolean',
        ]);

        $results = $this->pricingService->bulkOptimizePrices(
            $validated['category_id'] ?? null,
            $validated['dry_run'] ?? true
        );

        return response()->json($results);
    }

    /**
     * Check surge conditions
     * GET /api/pricing/check-surge/{productId}
     */
    public function checkSurgeConditions($productId)
    {
        $product = Product::findOrFail($productId);
        $surge = $this->pricingService->checkSurgePricingConditions($productId);

        if ($surge) {
            return response()->json([
                'should_activate' => true,
                'surge' => $surge,
            ]);
        }

        return response()->json([
            'should_activate' => false,
            'message' => 'No surge conditions detected',
        ]);
    }
}
