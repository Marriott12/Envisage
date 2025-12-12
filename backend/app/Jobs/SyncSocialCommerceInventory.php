<?php

namespace App\Jobs;

use App\Services\SocialCommerceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncSocialCommerceInventory implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $platform;

    /**
     * Create a new job instance.
     *
     * @param string|null $platform
     * @return void
     */
    public function __construct($platform = null)
    {
        $this->platform = $platform;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(SocialCommerceService $service)
    {
        try {
            $platforms = $this->platform ? [$this->platform] : ['instagram', 'facebook', 'tiktok'];
            
            Log::info("Starting social commerce inventory sync for platforms: " . implode(', ', $platforms));
            
            $products = \App\Models\SocialCommerceProduct::whereIn('platform', $platforms)
                ->active()
                ->with('product')
                ->get();

            $synced = 0;
            foreach ($products as $socialProduct) {
                try {
                    $service->updateInventory($socialProduct->product_id, [$socialProduct->platform]);
                    $synced++;
                } catch (\Exception $e) {
                    Log::error("Failed to sync inventory for product {$socialProduct->product_id}: " . $e->getMessage());
                }
            }
            
            Log::info("Social commerce inventory sync completed. Synced {$synced} products.");
        } catch (\Exception $e) {
            Log::error("Social commerce inventory sync failed: " . $e->getMessage());
            throw $e;
        }
    }
}
