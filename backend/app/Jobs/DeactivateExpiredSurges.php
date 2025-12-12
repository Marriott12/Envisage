<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\SurgePricingEvent;
use App\Services\DynamicPricingService;
use Illuminate\Support\Facades\Log;

class DeactivateExpiredSurges implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     */
    public function handle(DynamicPricingService $pricingService)
    {
        Log::info('Starting expired surge pricing cleanup');

        // Find expired surges that are still marked as active
        $expiredSurges = SurgePricingEvent::where('is_active', true)
            ->whereNotNull('surge_ended_at')
            ->where('surge_ended_at', '<', now())
            ->get();

        $deactivated = 0;

        foreach ($expiredSurges as $surge) {
            try {
                // Deactivate surge
                $surge->deactivate();

                // Revert to optimal price
                $optimal = $pricingService->calculateOptimalPrice($surge->product_id);
                if ($optimal) {
                    $pricingService->applyPriceChange(
                        $surge->product_id,
                        $optimal['recommended_price'],
                        'manual',
                        null,
                        null,
                        "Surge pricing expired - reverted to optimal price"
                    );
                }

                $deactivated++;
                Log::info("Deactivated expired surge for product {$surge->product_id}");
            } catch (\Exception $e) {
                Log::error("Failed to deactivate surge {$surge->id}: {$e->getMessage()}");
            }
        }

        Log::info("Surge cleanup complete. Deactivated {$deactivated} expired surges.");
    }
}
