<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Setting;

class SettingsSeeder extends Seeder
{
    public function run()
    {
        $defaults = [
            // Email Settings
            ['key' => 'mail_driver', 'value' => 'smtp', 'group' => 'email', 'type' => 'text', 'description' => 'Email driver (smtp, sendmail, mailgun)', 'is_public' => false],
            ['key' => 'mail_host', 'value' => 'smtp.gmail.com', 'group' => 'email', 'type' => 'text', 'description' => 'SMTP host', 'is_public' => false],
            ['key' => 'mail_port', 'value' => '587', 'group' => 'email', 'type' => 'number', 'description' => 'SMTP port', 'is_public' => false],
            ['key' => 'mail_username', 'value' => '', 'group' => 'email', 'type' => 'text', 'description' => 'SMTP username', 'is_public' => false],
            ['key' => 'mail_password', 'value' => '', 'group' => 'email', 'type' => 'password', 'description' => 'SMTP password', 'is_public' => false],
            ['key' => 'mail_encryption', 'value' => 'tls', 'group' => 'email', 'type' => 'text', 'description' => 'Encryption (tls, ssl)', 'is_public' => false],
            ['key' => 'mail_from_address', 'value' => 'noreply@envisagezm.com', 'group' => 'email', 'type' => 'text', 'description' => 'From email address', 'is_public' => false],
            ['key' => 'mail_from_name', 'value' => 'Envisage Marketplace', 'group' => 'email', 'type' => 'text', 'description' => 'From name', 'is_public' => false],
            
            // Payment Settings
            ['key' => 'stripe_secret_key', 'value' => '', 'group' => 'payment', 'type' => 'password', 'description' => 'Stripe secret key', 'is_public' => false],
            ['key' => 'stripe_webhook_secret', 'value' => '', 'group' => 'payment', 'type' => 'password', 'description' => 'Stripe webhook secret', 'is_public' => false],
            ['key' => 'stripe_public_key', 'value' => '', 'group' => 'payment', 'type' => 'text', 'is_public' => true, 'description' => 'Stripe publishable key'],
            ['key' => 'payment_currency', 'value' => 'usd', 'group' => 'payment', 'type' => 'text', 'is_public' => true, 'description' => 'Default currency'],
            
            // SEO Settings
            ['key' => 'site_name', 'value' => 'Envisage Marketplace', 'group' => 'seo', 'type' => 'text', 'is_public' => true, 'description' => 'Site name'],
            ['key' => 'site_description', 'value' => 'Buy and sell quality products online', 'group' => 'seo', 'type' => 'text', 'is_public' => true, 'description' => 'Site description'],
            ['key' => 'site_keywords', 'value' => 'marketplace, ecommerce, shop', 'group' => 'seo', 'type' => 'text', 'is_public' => true, 'description' => 'Site keywords'],
            ['key' => 'meta_image', 'value' => '', 'group' => 'seo', 'type' => 'text', 'is_public' => true, 'description' => 'Default meta image URL'],
            
            // General Settings
            ['key' => 'site_logo', 'value' => '', 'group' => 'general', 'type' => 'text', 'is_public' => true, 'description' => 'Site logo URL'],
            ['key' => 'frontend_url', 'value' => 'http://localhost:3000', 'group' => 'general', 'type' => 'text', 'description' => 'Frontend URL', 'is_public' => false],
            ['key' => 'enable_registration', 'value' => 'true', 'group' => 'general', 'type' => 'boolean', 'is_public' => true, 'description' => 'Enable user registration'],
            ['key' => 'maintenance_mode', 'value' => 'false', 'group' => 'general', 'type' => 'boolean', 'is_public' => true, 'description' => 'Maintenance mode'],
        ];

        foreach ($defaults as $default) {
            Setting::firstOrCreate(
                ['key' => $default['key']],
                $default
            );
        }

        echo "Settings seeded successfully!\n";
    }
}
