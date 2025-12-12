<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Product;
use App\Models\DemandForecast;
use Illuminate\Support\Facades\Log;

class CalculateDemandForecasts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $productIds;
    protected $daysAhead;

    /**
     * Create a new job instance.
     *
     * @param array|null $productIds Specific products to forecast (null = all products)
     * @param int $daysAhead Number of days to forecast ahead
     */
    public function __construct($productIds = null, $daysAhead = 7)
    {
        $this->productIds = $productIds;
        $this->daysAhead = $daysAhead;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        Log::info('Starting demand forecast calculation');

        $query = Product::where('is_active', true);

        if ($this->productIds) {
            $query->whereIn('id', $this->productIds);
        }

        $products = $query->get();
        $forecastsCreated = 0;

        foreach ($products as $product) {
            try {
                for ($i = 1; $i <= $this->daysAhead; $i++) {
                    $forecastDate = today()->addDays($i);
                    
                    $forecast = DemandForecast::calculateForecast(
                        $product->id,
                        $forecastDate,
                        30 // Use 30 days of historical data
                    );

                    if ($forecast) {
                        $forecastsCreated++;
                    }
                }
            } catch (\Exception $e) {
                Log::error("Failed to calculate forecast for product {$product->id}: {$e->getMessage()}");
            }
        }

        Log::info("Demand forecast calculation complete. Created/updated {$forecastsCreated} forecasts.");
    }
}
