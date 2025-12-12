<?php

namespace App\Jobs;

use App\Services\SocialCommerceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ImportSocialCommerceOrders implements ShouldQueue
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
            
            Log::info("Starting social commerce order import for platforms: " . implode(', ', $platforms));
            
            foreach ($platforms as $platform) {
                try {
                    $syncLog = $service->importOrders($platform);
                    Log::info("Imported {$syncLog->items_successful} orders from {$platform}");
                } catch (\Exception $e) {
                    Log::error("Failed to import orders from {$platform}: " . $e->getMessage());
                }
            }
            
            Log::info("Social commerce order import completed.");
        } catch (\Exception $e) {
            Log::error("Social commerce order import failed: " . $e->getMessage());
            throw $e;
        }
    }
}
