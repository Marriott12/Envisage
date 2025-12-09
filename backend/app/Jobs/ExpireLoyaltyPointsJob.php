<?php

namespace App\Jobs;

use App\Models\LoyaltyTransaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ExpireLoyaltyPointsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle()
    {
        // Get all transactions that are expiring
        $expiringTransactions = LoyaltyTransaction::where('type', 'earned')
            ->where('expires_at', '<=', now())
            ->whereNull('expired_at')
            ->get();

        foreach ($expiringTransactions as $transaction) {
            // Deduct points from user
            $loyaltyPoints = $transaction->user->loyaltyPoints;
            
            if ($loyaltyPoints && $loyaltyPoints->total_points >= $transaction->points) {
                $loyaltyPoints->total_points -= $transaction->points;
                $loyaltyPoints->save();

                // Create expiration record
                LoyaltyTransaction::create([
                    'user_id' => $transaction->user_id,
                    'points' => -$transaction->points,
                    'type' => 'expired',
                    'source' => 'expiration',
                    'description' => 'Points expired from ' . $transaction->source,
                    'balance_after' => $loyaltyPoints->total_points,
                ]);

                $transaction->update(['expired_at' => now()]);
            }
        }
    }
}
