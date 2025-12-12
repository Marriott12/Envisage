<?php

namespace App\Jobs;

use App\Models\VelocityTracking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CleanupVelocityTracking implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            // Clean up tracking windows older than 24 hours
            $deleted = VelocityTracking::cleanup(24);
            
            Log::info('Cleaned up velocity tracking records', [
                'count' => $deleted
            ]);
        } catch (\Exception $e) {
            Log::error('Error cleaning up velocity tracking', [
                'error' => $e->getMessage()
            ]);
        }
    }
}
