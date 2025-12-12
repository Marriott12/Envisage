<?php

namespace App\Jobs;

use App\Services\ReferralService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessReferralConversion implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $userId;
    protected $orderId;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($userId, $orderId)
    {
        $this->userId = $userId;
        $this->orderId = $orderId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(ReferralService $referralService)
    {
        \Log::info("Processing referral conversion for user {$this->userId}, order {$this->orderId}");

        $result = $referralService->processConversion($this->userId, $this->orderId);

        if ($result) {
            \Log::info("Referral conversion processed successfully");
        } else {
            \Log::info("No referral conversion found or already processed");
        }
    }
}
