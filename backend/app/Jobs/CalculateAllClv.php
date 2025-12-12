<?php

namespace App\Jobs;

use App\Services\SegmentationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CalculateAllClv implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(SegmentationService $segmentationService)
    {
        try {
            $calculated = $segmentationService->calculateAllClv();
            Log::info('CLV calculated for all customers', ['count' => $calculated]);
        } catch (\Exception $e) {
            Log::error('Error calculating CLV', ['error' => $e->getMessage()]);
        }
    }
}
