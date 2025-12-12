<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAnalyticsTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Analytic Events - Track all user interactions
        Schema::create('analytic_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('session_id', 100)->index();
            $table->string('event_type', 50)->index(); // page_view, product_view, add_to_cart, purchase, etc.
            $table->string('event_category', 50)->nullable()->index();
            $table->string('event_action', 100)->nullable();
            $table->string('event_label', 255)->nullable();
            $table->json('properties')->nullable(); // Additional event data
            $table->string('page_url', 500)->nullable();
            $table->string('referrer', 500)->nullable();
            $table->string('utm_source', 100)->nullable();
            $table->string('utm_medium', 100)->nullable();
            $table->string('utm_campaign', 100)->nullable();
            $table->string('device_type', 20)->nullable(); // desktop, mobile, tablet
            $table->string('browser', 50)->nullable();
            $table->string('os', 50)->nullable();
            $table->string('country', 2)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->decimal('revenue', 10, 2)->nullable(); // For purchase events
            $table->timestamps();
            
            $table->index(['event_type', 'created_at']);
            $table->index(['user_id', 'event_type']);
            $table->index(['session_id', 'created_at']);
        });

        // User Sessions - Track user browsing sessions
        Schema::create('user_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('session_id', 100)->unique();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->timestamp('started_at')->index();
            $table->timestamp('ended_at')->nullable();
            $table->integer('duration_seconds')->nullable();
            $table->integer('page_views')->default(0);
            $table->integer('events_count')->default(0);
            $table->string('entry_page', 500)->nullable();
            $table->string('exit_page', 500)->nullable();
            $table->string('referrer', 500)->nullable();
            $table->string('utm_source', 100)->nullable();
            $table->string('utm_medium', 100)->nullable();
            $table->string('utm_campaign', 100)->nullable();
            $table->string('device_type', 20)->nullable();
            $table->string('browser', 50)->nullable();
            $table->string('os', 50)->nullable();
            $table->string('country', 2)->nullable();
            $table->boolean('converted')->default(false); // Did user make a purchase?
            $table->decimal('revenue', 10, 2)->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'started_at']);
            $table->index(['converted', 'started_at']);
        });

        // Conversion Funnels - Track user journey through conversion funnels
        Schema::create('conversion_funnels', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->json('steps'); // Array of funnel steps
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Funnel Events - Track user progress through funnels
        Schema::create('funnel_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('funnel_id')->constrained('conversion_funnels')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('session_id', 100)->index();
            $table->integer('step_index'); // Which step in the funnel
            $table->string('step_name', 100);
            $table->boolean('completed')->default(false);
            $table->timestamp('entered_at');
            $table->timestamp('completed_at')->nullable();
            $table->integer('time_to_complete')->nullable(); // Seconds
            $table->boolean('dropped_off')->default(false);
            $table->timestamps();
            
            $table->index(['funnel_id', 'step_index', 'created_at']);
            $table->index(['session_id', 'funnel_id']);
        });

        // Business Metrics - Store aggregated business KPIs
        Schema::create('business_metrics', function (Blueprint $table) {
            $table->id();
            $table->date('date')->index();
            $table->string('metric_type', 50)->index(); // revenue, orders, users, sessions, etc.
            $table->string('dimension', 50)->nullable(); // Category, product, channel, etc.
            $table->string('dimension_value', 100)->nullable();
            $table->decimal('value', 15, 2);
            $table->integer('count')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->unique(['date', 'metric_type', 'dimension', 'dimension_value'], 'biz_metrics_unique');
            $table->index(['metric_type', 'date']);
        });

        // Cohort Analysis - Track user cohorts over time
        Schema::create('cohort_analyses', function (Blueprint $table) {
            $table->id();
            $table->date('cohort_date')->index(); // When cohort was created
            $table->string('cohort_type', 50)->index(); // registration, first_purchase, etc.
            $table->integer('cohort_size'); // Number of users in cohort
            $table->integer('period_number'); // 0, 1, 2, 3... (days/weeks/months after cohort_date)
            $table->string('period_type', 20); // day, week, month
            $table->integer('retained_users')->default(0);
            $table->decimal('retention_rate', 5, 2)->default(0);
            $table->decimal('revenue', 10, 2)->default(0);
            $table->decimal('ltv', 10, 2)->default(0); // Lifetime value
            $table->timestamps();
            
            $table->unique(['cohort_date', 'cohort_type', 'period_number', 'period_type'], 'cohort_unique');
        });

        // Product Analytics - Track product-specific metrics
        Schema::create('product_analytics', function (Blueprint $table) {
            $table->id();
            $table->date('date')->index();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->integer('views')->default(0);
            $table->integer('unique_views')->default(0);
            $table->integer('add_to_cart')->default(0);
            $table->integer('add_to_wishlist')->default(0);
            $table->integer('purchases')->default(0);
            $table->decimal('revenue', 10, 2)->default(0);
            $table->decimal('conversion_rate', 5, 2)->default(0);
            $table->decimal('avg_time_on_page', 8, 2)->default(0); // Seconds
            $table->integer('bounce_count')->default(0);
            $table->timestamps();
            
            $table->unique(['date', 'product_id'], 'product_analytics_unique');
            $table->index(['product_id', 'date']);
        });

        // User Behavior Scores - ML-generated user behavior scores
        Schema::create('user_behavior_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->decimal('engagement_score', 5, 2)->default(0); // 0-100
            $table->decimal('purchase_propensity', 5, 2)->default(0); // 0-100
            $table->decimal('churn_risk', 5, 2)->default(0); // 0-100
            $table->decimal('lifetime_value_prediction', 10, 2)->default(0);
            $table->string('user_segment', 50)->nullable(); // VIP, Active, At Risk, etc.
            $table->json('preferences')->nullable(); // Product categories, brands, etc.
            $table->timestamp('last_calculated_at');
            $table->timestamps();
            
            $table->unique('user_id');
            $table->index('user_segment');
        });

        // A/B Test Results
        Schema::create('ab_test_results', function (Blueprint $table) {
            $table->id();
            $table->string('test_name', 100)->index();
            $table->string('variant', 50)->index(); // A, B, C, etc.
            $table->date('date')->index();
            $table->integer('impressions')->default(0);
            $table->integer('conversions')->default(0);
            $table->decimal('conversion_rate', 5, 2)->default(0);
            $table->decimal('revenue', 10, 2)->default(0);
            $table->decimal('avg_order_value', 10, 2)->default(0);
            $table->timestamps();
            
            $table->unique(['test_name', 'variant', 'date'], 'ab_test_unique');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ab_test_results');
        Schema::dropIfExists('user_behavior_scores');
        Schema::dropIfExists('product_analytics');
        Schema::dropIfExists('cohort_analyses');
        Schema::dropIfExists('business_metrics');
        Schema::dropIfExists('funnel_events');
        Schema::dropIfExists('conversion_funnels');
        Schema::dropIfExists('user_sessions');
        Schema::dropIfExists('analytic_events');
    }
}
