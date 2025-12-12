<?php

namespace App\Jobs;

use App\Services\SegmentationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class TriggerChurnInterventions implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(SegmentationService $segmentationService)
    {
        try {
            $triggered = $segmentationService->triggerChurnInterventions();
            Log::info('Churn interventions triggered', ['count' => $triggered]);
        } catch (\Exception $e) {
            Log::error('Error triggering churn interventions', ['error' => $e->getMessage()]);
        }
    }
}
