<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDynamicPricingTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Price Rules - Define pricing strategies
        Schema::create('price_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->foreignId('product_id')->nullable()->constrained('products')->onDelete('cascade');
            $table->foreignId('category_id')->nullable()->constrained('categories')->onDelete('cascade');
            $table->string('rule_type', 30); // demand_based, competitor_based, time_based, inventory_based
            $table->decimal('min_price', 10, 2)->nullable(); // Minimum allowed price
            $table->decimal('max_price', 10, 2)->nullable(); // Maximum allowed price
            $table->decimal('target_margin', 5, 2)->default(30.00); // Target profit margin %
            $table->json('conditions')->nullable(); // Rule conditions
            $table->json('adjustments')->nullable(); // Price adjustments
            $table->integer('priority')->default(0); // Higher priority rules apply first
            $table->boolean('is_active')->default(true);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();
            
            $table->index(['product_id', 'is_active']);
            $table->index(['category_id', 'is_active']);
        });

        // Price History - Track all price changes
        Schema::create('price_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->decimal('old_price', 10, 2);
            $table->decimal('new_price', 10, 2);
            $table->decimal('change_percentage', 6, 2);
            $table->string('change_reason', 50); // manual, rule_based, demand, competitor, surge
            $table->foreignId('rule_id')->nullable()->constrained('price_rules')->onDelete('set null');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null'); // If manual
            $table->text('notes')->nullable();
            $table->timestamp('changed_at');
            $table->timestamps();
            
            $table->index('product_id');
            $table->index('changed_at');
        });

        // Competitor Prices - Track competitor pricing
        Schema::create('competitor_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->string('competitor_name', 100);
            $table->string('competitor_url', 500)->nullable();
            $table->decimal('competitor_price', 10, 2);
            $table->decimal('our_price', 10, 2);
            $table->decimal('price_difference', 10, 2);
            $table->decimal('price_diff_percentage', 6, 2);
            $table->string('product_match_quality', 20)->default('high'); // high, medium, low
            $table->boolean('in_stock')->default(true);
            $table->timestamp('scraped_at');
            $table->timestamps();
            
            $table->index(['product_id', 'scraped_at']);
            $table->index('competitor_name');
        });

        // Demand Forecasts - AI demand predictions
        Schema::create('demand_forecasts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->date('forecast_date');
            $table->integer('predicted_demand'); // Units expected to sell
            $table->decimal('confidence_score', 4, 2)->default(0); // 0-1 confidence
            $table->integer('actual_sales')->nullable(); // Actual units sold (for accuracy tracking)
            $table->string('demand_level', 20); // low, normal, high, surge
            $table->json('factors')->nullable(); // Factors influencing demand
            $table->decimal('recommended_price', 10, 2)->nullable();
            $table->timestamp('calculated_at');
            $table->timestamps();
            
            $table->unique(['product_id', 'forecast_date']);
            $table->index('forecast_date');
        });

        // Price Experiments - A/B testing for pricing
        Schema::create('price_experiments', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->decimal('control_price', 10, 2); // Original price
            $table->decimal('variant_price', 10, 2); // Test price
            $table->string('status', 20)->default('active'); // active, paused, completed
            $table->integer('control_impressions')->default(0);
            $table->integer('variant_impressions')->default(0);
            $table->integer('control_sales')->default(0);
            $table->integer('variant_sales')->default(0);
            $table->decimal('control_revenue', 10, 2)->default(0);
            $table->decimal('variant_revenue', 10, 2)->default(0);
            $table->decimal('control_conversion_rate', 5, 2)->default(0);
            $table->decimal('variant_conversion_rate', 5, 2)->default(0);
            $table->string('winner', 20)->nullable(); // control, variant, no_difference
            $table->decimal('confidence_level', 4, 2)->nullable(); // Statistical significance
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();
            
            $table->index(['product_id', 'status']);
        });

        // Surge Pricing Events - Track high-demand periods
        Schema::create('surge_pricing_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->nullable()->constrained('products')->onDelete('cascade');
            $table->foreignId('category_id')->nullable()->constrained('categories')->onDelete('cascade');
            $table->string('event_type', 30); // flash_sale, holiday, stock_low, high_traffic
            $table->decimal('surge_multiplier', 4, 2)->default(1.00); // 1.0 = no change, 1.5 = +50%
            $table->integer('demand_spike')->nullable(); // % increase in demand
            $table->integer('stock_level')->nullable();
            $table->timestamp('surge_started_at');
            $table->timestamp('surge_ended_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['product_id', 'is_active']);
            $table->index('surge_started_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('surge_pricing_events');
        Schema::dropIfExists('price_experiments');
        Schema::dropIfExists('demand_forecasts');
        Schema::dropIfExists('competitor_prices');
        Schema::dropIfExists('price_history');
        Schema::dropIfExists('price_rules');
    }
}
