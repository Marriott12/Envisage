<?php

namespace App\Jobs;

use App\Services\ReferralService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ApprovePendingRewards implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $daysOld;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($daysOld = 7)
    {
        $this->daysOld = $daysOld;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(ReferralService $referralService)
    {
        \Log::info("Approving pending rewards older than {$this->daysOld} days");

        $approved = $referralService->approvePendingRewards($this->daysOld);

        \Log::info("Approved {$approved} pending rewards");
    }
}
