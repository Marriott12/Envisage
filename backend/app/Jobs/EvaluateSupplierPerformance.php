<?php

namespace App\Jobs;

use App\Services\InventoryForecastingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class EvaluateSupplierPerformance implements ShouldQueue
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
            Log::info("Starting supplier performance evaluation");
            
            // Evaluate all suppliers for last month
            $evaluated = $service->evaluateAllSuppliers();
            
            Log::info("Supplier performance evaluation completed. Evaluated {$evaluated} suppliers.");
        } catch (\Exception $e) {
            Log::error("Supplier performance evaluation failed: " . $e->getMessage());
            throw $e;
        }
    }
}
