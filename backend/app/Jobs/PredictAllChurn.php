<?php

namespace App\Jobs;

use App\Services\SegmentationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PredictAllChurn implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(SegmentationService $segmentationService)
    {
        try {
            $predicted = $segmentationService->predictAllChurn();
            Log::info('Churn predictions calculated', ['count' => $predicted]);
        } catch (\Exception $e) {
            Log::error('Error predicting churn', ['error' => $e->getMessage()]);
        }
    }
}
