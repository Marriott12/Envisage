<?php

namespace App\Jobs;

use App\Services\RecommendationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GeneratePersonalizedRecommendations implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(RecommendationService $recommendationService)
    {
        \Log::info("Starting bulk recommendation generation");

        $generated = $recommendationService->generateBulkRecommendations();

        \Log::info("Generated recommendations for {$generated} users");
    }
}
