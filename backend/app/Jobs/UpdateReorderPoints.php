<?php

namespace App\Jobs;

use App\Services\InventoryForecastingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class UpdateReorderPoints implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(InventoryForecastingService $service)
    {
        try {
            Log::info("Starting reorder points update");
            
            $updated = $service->updateAllReorderPoints();
            
            Log::info("Reorder points update completed. Updated {$updated} reorder points.");
        } catch (\Exception $e) {
            Log::error("Reorder points update failed: " . $e->getMessage());
            throw $e;
        }
    }
}
