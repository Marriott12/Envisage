<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Product;
use App\Models\PriceRule;
use App\Services\DynamicPricingService;
use Illuminate\Support\Facades\Log;

class ApplyPricingRules implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $categoryId;
    protected $ruleId;

    /**
     * Create a new job instance.
     *
     * @param int|null $categoryId Apply rules for specific category
     * @param int|null $ruleId Apply specific rule only
     */
    public function __construct($categoryId = null, $ruleId = null)
    {
        $this->categoryId = $categoryId;
        $this->ruleId = $ruleId;
    }

    /**
     * Execute the job.
     */
    public function handle(DynamicPricingService $pricingService)
    {
        Log::info('Starting automated pricing rule application');

        $rulesQuery = PriceRule::active()->byPriority();

        if ($this->ruleId) {
            $rulesQuery->where('id', $this->ruleId);
        } elseif ($this->categoryId) {
            $rulesQuery->where('category_id', $this->categoryId);
        }

        $rules = $rulesQuery->get();
        $pricesUpdated = 0;

        foreach ($rules as $rule) {
            try {
                // Get affected products
                $productsQuery = Product::where('is_active', true);

                if ($rule->product_id) {
                    $productsQuery->where('id', $rule->product_id);
                } elseif ($rule->category_id) {
                    $productsQuery->where('category_id', $rule->category_id);
                }

                $products = $productsQuery->get();

                foreach ($products as $product) {
                    // Build context
                    $context = $pricingService->buildPricingContext($product);

                    // Check if rule is applicable
                    if ($rule->checkConditions($context)) {
                        $newPrice = $rule->calculatePrice($product->price, $context);

                        // Only apply if price changed significantly (>1% difference)
                        $priceDiff = abs($newPrice - $product->price);
                        $priceDiffPercentage = $product->price > 0 ? ($priceDiff / $product->price) * 100 : 0;

                        if ($priceDiffPercentage > 1) {
                            $pricingService->applyPriceChange(
                                $product->id,
                                $newPrice,
                                'rule_based',
                                $rule->id,
                                null,
                                "Automated rule application: {$rule->name}"
                            );

                            $pricesUpdated++;
                            Log::info("Applied rule '{$rule->name}' to product {$product->id}: {$product->price} -> {$newPrice}");
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::error("Failed to apply rule {$rule->id}: {$e->getMessage()}");
            }
        }

        Log::info("Pricing rule application complete. Updated {$pricesUpdated} prices.");
    }
}
