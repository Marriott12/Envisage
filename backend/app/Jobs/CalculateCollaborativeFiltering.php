<?php

namespace App\Jobs;

use App\Models\CollaborativeFilteringData;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CalculateCollaborativeFiltering implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $type;

    /**
     * Create a new job instance.
     *
     * @param string $type 'user' or 'item'
     */
    public function __construct($type = 'both')
    {
        $this->type = $type;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        \Log::info("Starting collaborative filtering calculation: {$this->type}");

        if ($this->type == 'user' || $this->type == 'both') {
            CollaborativeFilteringData::calculateUserSimilarity();
            \Log::info("User similarity calculation completed");
        }

        if ($this->type == 'item' || $this->type == 'both') {
            CollaborativeFilteringData::calculateItemSimilarity();
            \Log::info("Item similarity calculation completed");
        }

        \Log::info("Collaborative filtering calculation finished");
    }
}
