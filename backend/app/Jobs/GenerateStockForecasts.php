<?php

namespace App\Jobs;

use App\Services\InventoryForecastingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateStockForecasts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $days;

    /**
     * Create a new job instance.
     *
     * @param int $days Number of days to forecast
     * @return void
     */
    public function __construct($days = 30)
    {
        $this->days = $days;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(InventoryForecastingService $service)
    {
        try {
            Log::info("Starting stock forecasts generation for {$this->days} days");
            
            $totalForecasts = $service->generateAllForecasts($this->days);
            
            Log::info("Stock forecasts generation completed. Generated {$totalForecasts} forecasts.");
        } catch (\Exception $e) {
            Log::error("Stock forecasts generation failed: " . $e->getMessage());
            throw $e;
        }
    }
}
