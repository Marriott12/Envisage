<?php

namespace App\Jobs;

use App\Models\Blacklist;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CleanupExpiredBlacklist implements ShouldQueue
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
            $cleaned = Blacklist::cleanupExpired();
            
            Log::info('Cleaned up expired blacklist entries', [
                'count' => $cleaned
            ]);
        } catch (\Exception $e) {
            Log::error('Error cleaning up blacklist', [
                'error' => $e->getMessage()
            ]);
        }
    }
}
