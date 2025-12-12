<?php

namespace App\Jobs;

use App\Services\SegmentationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CalculateAllRfmScores implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(SegmentationService $segmentationService)
    {
        try {
            $calculated = $segmentationService->calculateAllRfmScores();
            Log::info('RFM scores calculated', ['count' => $calculated]);
        } catch (\Exception $e) {
            Log::error('Error calculating RFM scores', ['error' => $e->getMessage()]);
        }
    }
}
