<?php

namespace App\Services;

use App\Models\Product;
use App\Models\PriceRule;
use App\Models\PriceHistory;
use App\Models\CompetitorPrice;
use App\Models\DemandForecast;
use App\Models\PriceExperiment;
use App\Models\SurgePricingEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class DynamicPricingService
{
    /**
     * Calculate optimal price for a product using AI and rules
     */
    public function calculateOptimalPrice($productId, $considerRules = true, $considerCompetitors = true, $considerDemand = true)
    {
        $product = Product::find($productId);
        if (!$product) {
            return null;
        }

        $basePrice = $product->price;
        $context = $this->buildPricingContext($product);
        
        // Check for active A/B test
        $activeExperiment = PriceExperiment::forProduct($productId)->active()->first();
        if ($activeExperiment) {
            return [
                'type' => 'experiment',
                'original_price' => $basePrice,
                'recommended_price' => $activeExperiment->getAssignedPrice(),
                'reason' => 'Active A/B test in progress',
                'experiment_id' => $activeExperiment->id,
            ];
        }

        // Check for active surge pricing
        $activeSurge = SurgePricingEvent::getActiveSurge($productId);
        if ($activeSurge) {
            $surgePrice = $activeSurge->calculateSurgePrice($basePrice);
            return [
                'type' => 'surge',
                'original_price' => $basePrice,
                'recommended_price' => $surgePrice,
                'reason' => 'Surge pricing active: ' . $activeSurge->event_type,
                'surge_multiplier' => $activeSurge->surge_multiplier,
                'surge_event_id' => $activeSurge->id,
            ];
        }

        $recommendedPrice = $basePrice;
        $reasons = [];

        // Apply pricing rules (priority-based)
        if ($considerRules) {
            $ruleResult = $this->applyPricingRules($product, $context);
            if ($ruleResult) {
                $recommendedPrice = $ruleResult['price'];
                $reasons[] = $ruleResult['reason'];
            }
        }

        // Consider competitor pricing
        if ($considerCompetitors) {
            $competitorResult = $this->considerCompetitorPricing($product, $recommendedPrice);
            if ($competitorResult) {
                $recommendedPrice = $competitorResult['price'];
                $reasons[] = $competitorResult['reason'];
            }
        }

        // Consider demand forecasting
        if ($considerDemand) {
            $demandResult = $this->considerDemandForecast($product, $recommendedPrice);
            if ($demandResult) {
                $recommendedPrice = $demandResult['price'];
                $reasons[] = $demandResult['reason'];
            }
        }

        return [
            'type' => 'optimized',
            'original_price' => $basePrice,
            'recommended_price' => round($recommendedPrice, 2),
            'change_amount' => round($recommendedPrice - $basePrice, 2),
            'change_percentage' => $basePrice > 0 ? round((($recommendedPrice - $basePrice) / $basePrice) * 100, 2) : 0,
            'reasons' => $reasons,
            'context' => $context,
        ];
    }

    /**
     * Apply pricing rules to product
     */
    protected function applyPricingRules($product, $context)
    {
        // Get applicable rules (product-specific first, then category)
        $rules = PriceRule::active()
            ->byPriority()
            ->where(function ($query) use ($product) {
                $query->where('product_id', $product->id)
                    ->orWhere('category_id', $product->category_id);
            })
            ->get();

        foreach ($rules as $rule) {
            if ($rule->checkConditions($context)) {
                $newPrice = $rule->calculatePrice($product->price, $context);
                if ($newPrice != $product->price) {
                    return [
                        'price' => $newPrice,
                        'reason' => "Applied {$rule->rule_type} rule: {$rule->name}",
                        'rule_id' => $rule->id,
                    ];
                }
            }
        }

        return null;
    }

    /**
     * Consider competitor pricing in recommendation
     */
    protected function considerCompetitorPricing($product, $currentRecommendation)
    {
        $competitivePosition = CompetitorPrice::getCompetitivePosition($product->id);
        
        if (!$competitivePosition) {
            return null; // No competitor data
        }

        $avgCompetitorPrice = $competitivePosition['avg_competitor_price'];
        $lowestCompetitorPrice = $competitivePosition['lowest_competitor_price'];

        // If we're significantly more expensive than average, consider adjustment
        if ($currentRecommendation > ($avgCompetitorPrice * 1.15)) {
            $suggestedPrice = $avgCompetitorPrice * 0.98; // 2% undercut
            return [
                'price' => $suggestedPrice,
                'reason' => "Adjusted to be competitive (we were {$competitivePosition['price_position']})",
            ];
        }

        // If we're significantly cheaper, we can increase price
        if ($currentRecommendation < ($avgCompetitorPrice * 0.85)) {
            $suggestedPrice = $avgCompetitorPrice * 0.95; // Match at 95%
            return [
                'price' => $suggestedPrice,
                'reason' => "Increased price to match competitor average",
            ];
        }

        return null; // Current price is competitive
    }

    /**
     * Consider demand forecast in pricing
     */
    protected function considerDemandForecast($product, $currentRecommendation)
    {
        // Get today's forecast
        $forecast = DemandForecast::forProduct($product->id)
            ->where('forecast_date', today())
            ->highConfidence(0.6)
            ->first();

        if (!$forecast || !$forecast->recommended_price) {
            return null;
        }

        // If demand is high/surge, we can increase price
        if (in_array($forecast->demand_level, [DemandForecast::LEVEL_HIGH, DemandForecast::LEVEL_SURGE])) {
            $priceIncrease = $forecast->demand_level === DemandForecast::LEVEL_SURGE ? 1.15 : 1.08;
            return [
                'price' => $currentRecommendation * $priceIncrease,
                'reason' => "High demand forecast ({$forecast->demand_level}): {$forecast->predicted_demand} units",
            ];
        }

        // If demand is low, consider decrease
        if ($forecast->demand_level === DemandForecast::LEVEL_LOW) {
            return [
                'price' => $currentRecommendation * 0.95,
                'reason' => "Low demand forecast: {$forecast->predicted_demand} units",
            ];
        }

        return null;
    }

    /**
     * Build pricing context from product and market data
     */
    public function buildPricingContext($product)
    {
        return [
            'product_id' => $product->id,
            'current_price' => $product->price,
            'stock_level' => $product->stock ?? 0,
            'category_id' => $product->category_id,
            'cost' => $product->cost ?? 0,
            'margin' => $product->price > 0 && $product->cost > 0 
                ? (($product->price - $product->cost) / $product->price) * 100 
                : 0,
            'views_today' => $this->getProductViews($product->id, 1),
            'sales_today' => $this->getProductSales($product->id, 1),
            'sales_week' => $this->getProductSales($product->id, 7),
            'competitor_avg_price' => CompetitorPrice::getAverageCompetitorPrice($product->id),
            'competitor_lowest_price' => CompetitorPrice::getLowestCompetitorPrice($product->id),
            'hour' => now()->hour,
            'day_of_week' => now()->dayOfWeek,
            'is_weekend' => now()->isWeekend(),
        ];
    }

    /**
     * Apply price change to product
     */
    public function applyPriceChange($productId, $newPrice, $reason = PriceHistory::REASON_MANUAL, $ruleId = null, $userId = null, $notes = null)
    {
        $product = Product::find($productId);
        if (!$product) {
            return false;
        }

        $oldPrice = $product->price;

        // Record price history
        PriceHistory::recordChange($productId, $oldPrice, $newPrice, $reason, $ruleId, $userId, $notes);

        // Update product price
        $product->update(['price' => $newPrice]);

        // Clear cache
        Cache::forget("product_price_{$productId}");

        return true;
    }

    /**
     * Generate demand forecast for product
     */
    public function generateDemandForecast($productId, $daysAhead = 7)
    {
        $forecasts = [];

        for ($i = 1; $i <= $daysAhead; $i++) {
            $forecastDate = today()->addDays($i);
            $forecast = DemandForecast::calculateForecast($productId, $forecastDate);
            if ($forecast) {
                $forecasts[] = $forecast;
            }
        }

        return $forecasts;
    }

    /**
     * Start A/B price experiment
     */
    public function startPriceExperiment($productId, $name, $variantPrice, $controlPrice = null)
    {
        $product = Product::find($productId);
        if (!$product) {
            return null;
        }

        $controlPrice = $controlPrice ?? $product->price;

        // Check if experiment already exists
        $existing = PriceExperiment::forProduct($productId)->active()->first();
        if ($existing) {
            return [
                'success' => false,
                'message' => 'An active experiment already exists for this product',
                'experiment_id' => $existing->id,
            ];
        }

        $experiment = PriceExperiment::startExperiment($productId, $name, $controlPrice, $variantPrice);

        return [
            'success' => true,
            'message' => 'Experiment started successfully',
            'experiment' => $experiment,
        ];
    }

    /**
     * Check and activate surge pricing conditions
     */
    public function checkSurgePricingConditions($productId)
    {
        return SurgePricingEvent::checkSurgeConditions($productId);
    }

    /**
     * Activate manual surge pricing
     */
    public function activateSurgePricing($productId, $eventType, $multiplier, $durationMinutes = 60)
    {
        $product = Product::find($productId);
        if (!$product) {
            return null;
        }

        $surge = SurgePricingEvent::activateSurge(
            $productId,
            $product->category_id,
            $eventType,
            $multiplier,
            null,
            $product->stock ?? 0,
            $durationMinutes
        );

        // Apply surge price
        $surgePrice = $surge->calculateSurgePrice($product->price);
        $this->applyPriceChange(
            $productId,
            $surgePrice,
            PriceHistory::REASON_SURGE,
            null,
            null,
            "Surge pricing activated: {$eventType}"
        );

        return $surge;
    }

    /**
     * Get product views count
     */
    protected function getProductViews($productId, $days = 1)
    {
        return DB::table('analytic_events')
            ->where('event_type', 'product_view')
            ->where('properties->product_id', $productId)
            ->where('created_at', '>=', now()->subDays($days))
            ->count();
    }

    /**
     * Get product sales count
     */
    protected function getProductSales($productId, $days = 1)
    {
        return DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('order_items.product_id', $productId)
            ->where('orders.created_at', '>=', now()->subDays($days))
            ->where('orders.status', 'completed')
            ->sum('order_items.quantity');
    }

    /**
     * Get pricing analytics for admin dashboard
     */
    public function getPricingAnalytics($days = 30)
    {
        $totalPriceChanges = PriceHistory::recent($days)->count();
        $priceIncreases = PriceHistory::recent($days)->priceIncreases()->count();
        $priceDecreases = PriceHistory::recent($days)->priceDecreases()->count();

        $changesByReason = PriceHistory::recent($days)
            ->select('change_reason', DB::raw('COUNT(*) as count'))
            ->groupBy('change_reason')
            ->get();

        $avgChangePercentage = PriceHistory::recent($days)->avg('change_percentage');

        $activeRules = PriceRule::active()->count();
        $activeExperiments = PriceExperiment::active()->count();
        $activeSurges = SurgePricingEvent::active()->current()->count();

        $topVolatileProducts = DB::table('price_history')
            ->select('product_id', DB::raw('COUNT(*) as change_count'), DB::raw('STDDEV(change_percentage) as volatility'))
            ->where('changed_at', '>=', now()->subDays($days))
            ->groupBy('product_id')
            ->orderBy('volatility', 'desc')
            ->limit(10)
            ->get();

        return [
            'summary' => [
                'total_price_changes' => $totalPriceChanges,
                'price_increases' => $priceIncreases,
                'price_decreases' => $priceDecreases,
                'avg_change_percentage' => round($avgChangePercentage, 2),
                'active_rules' => $activeRules,
                'active_experiments' => $activeExperiments,
                'active_surges' => $activeSurges,
            ],
            'changes_by_reason' => $changesByReason,
            'top_volatile_products' => $topVolatileProducts,
        ];
    }

    /**
     * Bulk apply pricing optimization
     */
    public function bulkOptimizePrices($categoryId = null, $dryRun = true)
    {
        $query = Product::query();
        
        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        $products = $query->get();
        $results = [];

        foreach ($products as $product) {
            $optimization = $this->calculateOptimalPrice($product->id);
            
            if ($optimization && $optimization['recommended_price'] != $product->price) {
                if (!$dryRun) {
                    $this->applyPriceChange(
                        $product->id,
                        $optimization['recommended_price'],
                        PriceHistory::REASON_RULE_BASED,
                        null,
                        null,
                        'Bulk optimization: ' . implode(', ', $optimization['reasons'])
                    );
                }

                $results[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'old_price' => $product->price,
                    'new_price' => $optimization['recommended_price'],
                    'change' => $optimization['change_amount'],
                    'change_percentage' => $optimization['change_percentage'],
                    'reasons' => $optimization['reasons'],
                    'applied' => !$dryRun,
                ];
            }
        }

        return [
            'total_products' => $products->count(),
            'products_optimized' => count($results),
            'dry_run' => $dryRun,
            'results' => $results,
        ];
    }
}
