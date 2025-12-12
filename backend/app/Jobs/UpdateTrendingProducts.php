<?php

namespace App\Jobs;

use App\Services\TrendingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdateTrendingProducts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $date;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($date = null)
    {
        $this->date = $date ?? today();
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(TrendingService $trendingService)
    {
        \Log::info("Updating trending products for date: {$this->date}");

        $trendingService->calculateTrending($this->date);

        \Log::info("Trending products updated successfully");
    }
}
