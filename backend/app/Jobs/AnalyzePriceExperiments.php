<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\PriceExperiment;
use Illuminate\Support\Facades\Log;

class AnalyzePriceExperiments implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $experimentId;

    /**
     * Create a new job instance.
     *
     * @param int|null $experimentId Specific experiment to analyze (null = all active)
     */
    public function __construct($experimentId = null)
    {
        $this->experimentId = $experimentId;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        Log::info('Starting price experiment analysis');

        $query = PriceExperiment::active();

        if ($this->experimentId) {
            $query->where('id', $this->experimentId);
        }

        $experiments = $query->get();
        $experimentsAnalyzed = 0;

        foreach ($experiments as $experiment) {
            try {
                // Check if experiment has enough data
                $minImpressions = 100;
                $minDuration = 3; // 3 days minimum

                $hasEnoughData = $experiment->control_impressions >= $minImpressions
                    && $experiment->variant_impressions >= $minImpressions;

                $hasRunLongEnough = $experiment->started_at->diffInDays(now()) >= $minDuration;

                if (!$hasEnoughData || !$hasRunLongEnough) {
                    Log::info("Experiment {$experiment->id} needs more data/time");
                    continue;
                }

                // Calculate statistical significance
                $confidence = $experiment->calculateStatisticalSignificance();

                // Determine winner
                $winner = $experiment->determineWinner(0.95, $minImpressions);

                // Auto-complete if statistically significant
                if ($confidence >= 0.95 && $winner !== PriceExperiment::WINNER_NONE) {
                    Log::info("Experiment {$experiment->id} reached statistical significance. Winner: {$winner}");
                    
                    // Optionally auto-complete after 7 days
                    if ($experiment->started_at->diffInDays(now()) >= 7) {
                        $experiment->completeExperiment();
                        Log::info("Auto-completed experiment {$experiment->id}");
                    }
                }

                $experimentsAnalyzed++;
            } catch (\Exception $e) {
                Log::error("Failed to analyze experiment {$experiment->id}: {$e->getMessage()}");
            }
        }

        Log::info("Experiment analysis complete. Analyzed {$experimentsAnalyzed} experiments.");
    }
}
