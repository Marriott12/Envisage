<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;

class DynamicConfigServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        // Only load settings if database is available and settings table exists
        try {
            if (Schema::hasTable('settings')) {
                $this->loadMailSettings();
                $this->loadPaymentSettings();
            }
        } catch (\Exception $e) {
            // Silently fail if database connection is not available
            // This allows config:cache to work even when database is not configured
        }
    }

    /**
     * Load email settings from database
     */
    protected function loadMailSettings()
    {
        try {
            $mailSettings = [
                'mail.default' => Setting::get('mail_driver', config('mail.default')),
                'mail.mailers.smtp.host' => Setting::get('mail_host', config('mail.mailers.smtp.host')),
                'mail.mailers.smtp.port' => Setting::get('mail_port', config('mail.mailers.smtp.port')),
                'mail.mailers.smtp.username' => Setting::get('mail_username', config('mail.mailers.smtp.username')),
                'mail.mailers.smtp.password' => Setting::get('mail_password', config('mail.mailers.smtp.password')),
                'mail.mailers.smtp.encryption' => Setting::get('mail_encryption', config('mail.mailers.smtp.encryption')),
                'mail.from.address' => Setting::get('mail_from_address', config('mail.from.address')),
                'mail.from.name' => Setting::get('mail_from_name', config('mail.from.name')),
            ];

            foreach ($mailSettings as $key => $value) {
                if ($value !== null) {
                    Config::set($key, $value);
                }
            }
        } catch (\Exception $e) {
            // If settings can't be loaded, use default config
            \Log::warning('Could not load mail settings from database: ' . $e->getMessage());
        }
    }

    /**
     * Load payment settings from database
     */
    protected function loadPaymentSettings()
    {
        try {
            $stripeSecret = Setting::get('stripe_secret_key');
            $stripePublic = Setting::get('stripe_public_key');
            $webhookSecret = Setting::get('stripe_webhook_secret');

            if ($stripeSecret) {
                Config::set('services.stripe.secret', $stripeSecret);
            }
            if ($stripePublic) {
                Config::set('services.stripe.key', $stripePublic);
            }
            if ($webhookSecret) {
                Config::set('services.stripe.webhook_secret', $webhookSecret);
            }
        } catch (\Exception $e) {
            \Log::warning('Could not load payment settings from database: ' . $e->getMessage());
        }
    }
}
