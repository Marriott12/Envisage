<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // Abandoned Cart Recovery Emails
        $schedule->call(function () {
            $carts = \App\Models\AbandonedCart::where('recovered', false)
                ->where('created_at', '>=', now()->subHours(1))
                ->where('created_at', '<=', now()->subMinutes(55))
                ->where('recovery_email_count', 0)
                ->get();

            foreach ($carts as $cart) {
                \App\Jobs\SendAbandonedCartEmailJob::dispatch($cart, '1_hour');
            }
        })->hourly();

        // Price Alerts Check
        $schedule->job(new \App\Jobs\ProcessPriceAlertsJob)->hourly();

        // Loyalty Points Expiration
        $schedule->job(new \App\Jobs\ExpireLoyaltyPointsJob)->daily();

        // Loyalty Tier Updates
        $schedule->job(new \App\Jobs\UpdateLoyaltyTiersJob)->weekly();

        // Low Stock Alerts
        $schedule->job(new \App\Jobs\CheckLowStockJob)->dailyAt('08:00');

        // Cleanup Expired Flash Sales
        $schedule->job(new \App\Jobs\CleanupExpiredFlashSalesJob)->hourly();

        // Subscription Renewal Reminders (3 days before)
        $schedule->call(function () {
            $subscriptions = \App\Models\SellerSubscription::where('status', 'active')
                ->where('auto_renew', true)
                ->whereDate('ends_at', now()->addDays(3)->toDateString())
                ->with('plan')
                ->get();

            foreach ($subscriptions as $subscription) {
                \Illuminate\Support\Facades\Mail::to($subscription->user->email)
                    ->send(new \App\Mail\SubscriptionRenewalMail($subscription, 3));
            }
        })->daily();

        // Clean old abandoned carts (30+ days)
        $schedule->call(function () {
            \App\Models\AbandonedCart::where('created_at', '<=', now()->subDays(30))
                ->where('recovered', false)
                ->delete();
        })->weekly();

        // Update frequently bought together recommendations
        $schedule->call(function () {
            // This could be resource-intensive, run weekly
            $recentOrders = \App\Models\Order::where('created_at', '>=', now()->subDays(7))
                ->where('status', 'completed')
                ->get();

            foreach ($recentOrders as $order) {
                app(\App\Http\Controllers\Api\BundleController::class)
                    ->updateFrequentlyBought($order->id);
            }
        })->weekly();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
