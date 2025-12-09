<?php

namespace App\Jobs;

use App\Mail\LowStockAlertMail;
use App\Models\LowStockAlert;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class CheckLowStockJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle()
    {
        $alerts = LowStockAlert::where('is_active', true)
            ->with(['product.seller'])
            ->get();

        foreach ($alerts as $alert) {
            if ($alert->shouldAlert()) {
                Mail::to($alert->product->seller->email)->send(
                    new LowStockAlertMail(
                        $alert->product,
                        $alert->current_quantity,
                        $alert->threshold_quantity
                    )
                );

                $alert->update(['last_alerted_at' => now()]);
            }
        }
    }
}
