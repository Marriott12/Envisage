<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds - Create roles and permissions for Envisage AI Platform
     *
     * @return void
     */
    public function run()
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create AI-related permissions
        $aiPermissions = [
            // Visual Search
            'use-visual-search',
            'upload-visual-search-images',
            
            // Recommendations
            'view-recommendations',
            'customize-recommendations',
            
            // Chatbot
            'use-chatbot',
            'escalate-chatbot',
            
            // Content Generation
            'generate-content',
            'approve-generated-content',
            
            // Sentiment Analysis
            'view-sentiment-analysis',
            
            // Fraud Detection
            'view-fraud-alerts',
            'review-fraud-alerts',
            'approve-fraud-decisions',
            
            // Predictive Analytics
            'view-predictive-analytics',
            'access-demand-forecast',
            'view-churn-predictions',
            
            // Dynamic Pricing
            'view-dynamic-pricing',
            'configure-dynamic-pricing',
            
            // AI Analytics
            'view-ai-metrics',
            'view-ai-costs',
            'export-ai-reports',
            
            // A/B Testing
            'create-ab-tests',
            'view-ab-results',
            'manage-ab-experiments',
        ];

        foreach ($aiPermissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Create general marketplace permissions
        $marketplacePermissions = [
            'manage-products',
            'manage-orders',
            'manage-users',
            'manage-categories',
            'view-reports',
            'manage-settings',
            'moderate-reviews',
        ];

        foreach ($marketplacePermissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Create roles and assign permissions
        
        // 1. Admin Role - Full access to everything
        $admin = Role::create(['name' => 'admin']);
        $admin->givePermissionTo(Permission::all());

        // 2. Seller Role - Manage own products, view analytics, generate content
        $seller = Role::create(['name' => 'seller']);
        $seller->givePermissionTo([
            'manage-products',
            'manage-orders',
            'view-recommendations',
            'generate-content',
            'approve-generated-content',
            'view-sentiment-analysis',
            'view-predictive-analytics',
            'access-demand-forecast',
            'view-dynamic-pricing',
            'configure-dynamic-pricing',
            'view-ai-metrics',
            'view-ai-costs',
            'use-chatbot',
            'use-visual-search',
            'moderate-reviews',
        ]);

        // 3. Customer Role - Basic AI features, recommendations, chatbot
        $customer = Role::create(['name' => 'customer']);
        $customer->givePermissionTo([
            'use-visual-search',
            'upload-visual-search-images',
            'view-recommendations',
            'customize-recommendations',
            'use-chatbot',
            'escalate-chatbot',
            'view-sentiment-analysis',
        ]);

        // 4. Premium Customer Role - Enhanced AI features
        $premiumCustomer = Role::create(['name' => 'premium_customer']);
        $premiumCustomer->givePermissionTo([
            'use-visual-search',
            'upload-visual-search-images',
            'view-recommendations',
            'customize-recommendations',
            'use-chatbot',
            'escalate-chatbot',
            'generate-content', // Premium feature
            'view-sentiment-analysis',
            'view-predictive-analytics',
        ]);

        // 5. Moderator Role - Content moderation and fraud review
        $moderator = Role::create(['name' => 'moderator']);
        $moderator->givePermissionTo([
            'moderate-reviews',
            'view-fraud-alerts',
            'review-fraud-alerts',
            'view-sentiment-analysis',
            'use-chatbot',
        ]);

        // 6. Data Analyst Role - Analytics and reporting
        $analyst = Role::create(['name' => 'data_analyst']);
        $analyst->givePermissionTo([
            'view-ai-metrics',
            'view-ai-costs',
            'export-ai-reports',
            'view-ab-results',
            'view-reports',
            'view-predictive-analytics',
            'access-demand-forecast',
            'view-churn-predictions',
            'view-sentiment-analysis',
        ]);

        $this->command->info('Roles and permissions created successfully!');
        $this->command->info('Created roles: admin, seller, customer, premium_customer, moderator, data_analyst');
        $this->command->info('Created ' . count($aiPermissions) . ' AI permissions + ' . count($marketplacePermissions) . ' marketplace permissions');
    }
}
