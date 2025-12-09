<?php

namespace App\Jobs;

use App\Models\UserLoyaltyPoint;
use App\Models\LoyaltyTransaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdateLoyaltyTiersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle()
    {
        // Get all loyalty point records
        $loyaltyRecords = UserLoyaltyPoint::all();

        foreach ($loyaltyRecords as $record) {
            $record->checkAndUpdateTier();
        }
    }
}
