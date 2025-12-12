<?php

namespace App\Console\Commands;

use App\Services\AnalyticsService;
use Illuminate\Console\Command;

class AggregateAnalytics extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'analytics:aggregate {--date= : Date to aggregate (Y-m-d format)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Aggregate analytics metrics for a specific date';

    protected $analyticsService;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(AnalyticsService $analyticsService)
    {
        parent::__construct();
        $this->analyticsService = $analyticsService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $date = $this->option('date') ?? now()->subDay()->format('Y-m-d');

        $this->info("Aggregating analytics for {$date}...");

        // Aggregate daily metrics
        $this->info("Aggregating daily metrics...");
        $this->analyticsService->aggregateDailyMetrics($date);

        // Calculate product analytics
        $this->info("Calculating product analytics...");
        $this->analyticsService->calculateProductAnalytics($date);

        $this->info("Analytics aggregation completed for {$date}");

        return 0;
    }
}
