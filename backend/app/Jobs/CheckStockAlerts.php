<?php

namespace App\Jobs;

use App\Services\InventoryForecastingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CheckStockAlerts implements ShouldQueue
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
            Log::info("Starting stock alerts check");
            
            // Generate new alerts
            $alertsCreated = $service->generateStockAlerts();
            
            // Auto-resolve invalid alerts
            $alertsResolved = $service->autoResolveAlerts();
            
            Log::info("Stock alerts check completed. Created {$alertsCreated} alerts, resolved {$alertsResolved} alerts.");
        } catch (\Exception $e) {
            Log::error("Stock alerts check failed: " . $e->getMessage());
            throw $e;
        }
    }
}
