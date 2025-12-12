<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Product;
use App\Models\SurgePricingEvent;
use App\Services\DynamicPricingService;
use Illuminate\Support\Facades\Log;

class MonitorSurgePricingConditions implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $productIds;

    /**
     * Create a new job instance.
     *
     * @param array|null $productIds Specific products to monitor (null = all products)
     */
    public function __construct($productIds = null)
    {
        $this->productIds = $productIds;
    }

    /**
     * Execute the job.
     */
    public function handle(DynamicPricingService $pricingService)
    {
        Log::info('Starting surge pricing condition monitoring');

        $query = Product::where('is_active', true);

        if ($this->productIds) {
            $query->whereIn('id', $this->productIds);
        }

        $products = $query->get();
        $surgesActivated = 0;

        foreach ($products as $product) {
            try {
                // Check if surge pricing already active
                $existingSurge = SurgePricingEvent::forProduct($product->id)
                    ->current()
                    ->first();

                if ($existingSurge) {
                    continue; // Already has active surge
                }

                // Check surge conditions
                $surge = $pricingService->checkSurgePricingConditions($product->id);

                if ($surge) {
                    $surgesActivated++;
                    Log::info("Auto-activated surge pricing for product {$product->id}: {$surge->event_type}");
                }
            } catch (\Exception $e) {
                Log::error("Failed to check surge conditions for product {$product->id}: {$e->getMessage()}");
            }
        }

        Log::info("Surge monitoring complete. Activated {$surgesActivated} surge pricing events.");
    }
}
