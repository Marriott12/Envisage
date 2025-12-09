<?php

namespace App\Jobs;

use App\Models\PriceAlert;
use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class ProcessPriceAlertsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle()
    {
        // Get all active price alerts
        $alerts = PriceAlert::where('is_active', true)
            ->with(['user', 'product'])
            ->get();

        foreach ($alerts as $alert) {
            $this->checkPriceAlert($alert);
        }
    }

    protected function checkPriceAlert($alert)
    {
        $product = $alert->product;

        // Check if price has dropped to target or below
        if ($product->price <= $alert->target_price) {
            // Send email notification
            Mail::to($alert->user->email)->send(
                new \App\Mail\PriceAlertMail($alert, $product)
            );

            // Mark as triggered
            $alert->update([
                'is_active' => false,
                'triggered_at' => now(),
            ]);
        }
    }
}
