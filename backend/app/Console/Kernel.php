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

        // ==================== MARKETING AUTOMATION ====================
        
        // Process scheduled automation executions (every 5 minutes)
        $schedule->command('automation:process')->everyFiveMinutes();

        // Check for abandoned carts (every 30 minutes)
        $schedule->job(new \App\Jobs\CheckAbandonedCarts)->everyThirtyMinutes();

        // ==================== ANALYTICS ====================
        
        // Aggregate daily analytics (runs at 1 AM)
        $schedule->command('analytics:aggregate')->dailyAt('01:00');

        // ==================== AI RECOMMENDATION ENGINE ====================
        
        // Update trending products (hourly)
        $schedule->job(new \App\Jobs\UpdateTrendingProducts)->hourly();

        // Calculate collaborative filtering (daily at 2 AM)
        $schedule->job(new \App\Jobs\CalculateCollaborativeFiltering('both'))->dailyAt('02:00');

        // Calculate association rules (frequently bought together - daily at 3 AM)
        $schedule->job(new \App\Jobs\CalculateAssociationRules)->dailyAt('03:00');

        // Generate personalized recommendations (every 6 hours)
        $schedule->job(new \App\Jobs\GeneratePersonalizedRecommendations)->cron('0 */6 * * *');

        // Clean up expired recommendation cache (daily at 4 AM)
        $schedule->call(function () {
            \App\Models\PersonalizedRecommendation::where('expires_at', '<', now())->delete();
        })->dailyAt('04:00');

        // ==================== REFERRAL PROGRAM ====================
        
        // Expire old referrals (daily)
        $schedule->job(new \App\Jobs\ExpireOldReferrals)->daily();

        // Auto-approve pending rewards after 7 days (fraud prevention window)
        $schedule->job(new \App\Jobs\ApprovePendingRewards(7))->dailyAt('05:00');

        // ==================== DYNAMIC PRICING ENGINE ====================
        
        // Calculate demand forecasts (every 6 hours)
        $schedule->job(new \App\Jobs\CalculateDemandForecasts(null, 7))->cron('0 */6 * * *');

        // Apply pricing rules (daily at 2 AM)
        $schedule->job(new \App\Jobs\ApplyPricingRules)->dailyAt('02:00');

        // Monitor surge pricing conditions (every hour)
        $schedule->job(new \App\Jobs\MonitorSurgePricingConditions)->hourly();

        // Analyze price experiments (daily at 6 AM)
        $schedule->job(new \App\Jobs\AnalyzePriceExperiments)->dailyAt('06:00');

        // Deactivate expired surge pricing (every 10 minutes)
        $schedule->job(new \App\Jobs\DeactivateExpiredSurges)->everyTenMinutes();

        // ==================== FRAUD DETECTION ====================
        
        // Cleanup expired blacklist entries (daily at 3 AM)
        $schedule->job(new \App\Jobs\CleanupExpiredBlacklist)->dailyAt('03:00');

        // Cleanup old velocity tracking (every 6 hours)
        $schedule->job(new \App\Jobs\CleanupVelocityTracking)->cron('0 */6 * * *');

        // ==================== CUSTOMER SEGMENTATION ====================
        
        // Calculate RFM scores (daily at 4 AM)
        $schedule->job(new \App\Jobs\CalculateAllRfmScores)->dailyAt('04:00');

        // Calculate CLV (daily at 5 AM)
        $schedule->job(new \App\Jobs\CalculateAllClv)->dailyAt('05:00');

        // Predict churn (daily at 6 AM)
        $schedule->job(new \App\Jobs\PredictAllChurn)->dailyAt('06:00');

        // Trigger churn interventions (daily at 8 AM)
        $schedule->job(new \App\Jobs\TriggerChurnInterventions)->dailyAt('08:00');

        // ==================== INVENTORY FORECASTING ====================
        
        // Generate stock forecasts (daily at 2 AM)
        $schedule->job(new \App\Jobs\GenerateStockForecasts)->dailyAt('02:00');

        // Update reorder points (daily at 7 AM)
        $schedule->job(new \App\Jobs\UpdateReorderPoints)->dailyAt('07:00');

        // Check stock alerts (every 4 hours)
        $schedule->job(new \App\Jobs\CheckStockAlerts)->cron('0 */4 * * *');

        // Evaluate supplier performance (monthly on 1st at 1 AM)
        $schedule->job(new \App\Jobs\EvaluateSupplierPerformance)->monthlyOn(1, '01:00');

        // ==================== SOCIAL COMMERCE ====================
        
        // Sync inventory to social platforms (every 2 hours)
        $schedule->job(new \App\Jobs\SyncSocialCommerceInventory)->cron('0 */2 * * *');

        // Import orders from social platforms (every hour)
        $schedule->job(new \App\Jobs\ImportSocialCommerceOrders)->hourly();
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
