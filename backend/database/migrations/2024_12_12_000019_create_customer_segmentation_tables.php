<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCustomerSegmentationTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Customer segments
        Schema::create('customer_segments', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('segment_type', ['rfm', 'behavioral', 'demographic', 'predictive', 'custom']);
            $table->json('criteria'); // Segmentation rules
            $table->integer('customer_count')->default(0);
            $table->decimal('avg_lifetime_value', 10, 2)->nullable();
            $table->decimal('avg_order_value', 10, 2)->nullable();
            $table->decimal('avg_order_frequency', 8, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_calculated_at')->nullable();
            $table->timestamps();
            
            $table->index(['segment_type', 'is_active']);
        });

        // Customer segment memberships
        Schema::create('customer_segment_memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('segment_id')->constrained('customer_segments')->onDelete('cascade');
            $table->json('segment_data')->nullable(); // Specific data for this membership
            $table->timestamp('joined_at');
            $table->timestamps();
            
            $table->unique(['user_id', 'segment_id']);
            $table->index('joined_at');
        });

        // RFM scores (Recency, Frequency, Monetary)
        Schema::create('rfm_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->onDelete('cascade');
            
            // Recency (days since last purchase)
            $table->integer('recency_days')->nullable();
            $table->integer('recency_score')->default(0); // 1-5
            
            // Frequency (number of purchases)
            $table->integer('frequency_count')->default(0);
            $table->integer('frequency_score')->default(0); // 1-5
            
            // Monetary (total spent)
            $table->decimal('monetary_value', 12, 2)->default(0);
            $table->integer('monetary_score')->default(0); // 1-5
            
            // Combined RFM score (111-555)
            $table->string('rfm_score', 3)->nullable(); // e.g., "543"
            $table->string('rfm_segment')->nullable(); // e.g., "Champions", "At Risk"
            
            // Additional metrics
            $table->decimal('avg_order_value', 10, 2)->default(0);
            $table->integer('total_orders')->default(0);
            $table->timestamp('first_purchase_at')->nullable();
            $table->timestamp('last_purchase_at')->nullable();
            
            $table->timestamps();
            
            $table->index(['rfm_score', 'rfm_segment']);
            $table->index(['recency_score', 'frequency_score', 'monetary_score']);
        });

        // Churn predictions
        Schema::create('churn_predictions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->decimal('churn_probability', 5, 4); // 0.0000 to 1.0000 (0% to 100%)
            $table->enum('churn_risk', ['low', 'medium', 'high', 'critical'])->default('low');
            $table->json('contributing_factors'); // Factors increasing churn risk
            $table->json('recommended_actions'); // Retention strategies
            $table->integer('days_until_predicted_churn')->nullable();
            $table->decimal('predicted_lifetime_value', 12, 2)->nullable();
            $table->boolean('intervention_triggered')->default(false);
            $table->timestamp('intervention_at')->nullable();
            $table->string('intervention_type')->nullable(); // email, discount, etc.
            $table->timestamp('predicted_at');
            $table->timestamps();
            
            $table->index(['user_id', 'predicted_at']);
            $table->index(['churn_risk', 'intervention_triggered']);
        });

        // Customer lifetime value calculations
        Schema::create('customer_lifetime_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->onDelete('cascade');
            
            // Historical CLV
            $table->decimal('historical_value', 12, 2)->default(0); // Total spent
            
            // Predicted CLV
            $table->decimal('predicted_value', 12, 2)->nullable(); // Predicted future value
            $table->decimal('predicted_12_month', 12, 2)->nullable();
            $table->decimal('predicted_24_month', 12, 2)->nullable();
            $table->decimal('predicted_36_month', 12, 2)->nullable();
            
            // CLV components
            $table->decimal('avg_purchase_value', 10, 2)->default(0);
            $table->decimal('purchase_frequency', 8, 2)->default(0); // Purchases per year
            $table->decimal('customer_lifespan_months', 8, 2)->default(0);
            $table->decimal('profit_margin', 5, 2)->default(0.30); // Default 30%
            
            // Customer value tier
            $table->enum('value_tier', ['bronze', 'silver', 'gold', 'platinum', 'vip'])->default('bronze');
            
            // Risk factors
            $table->decimal('retention_probability', 5, 4)->default(1.0000);
            $table->decimal('growth_rate', 5, 4)->default(0.0000); // Growth in spending
            
            $table->timestamp('calculated_at');
            $table->timestamps();
            
            $table->index('value_tier');
            $table->index('predicted_value');
        });

        // Next purchase predictions
        Schema::create('next_purchase_predictions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->date('predicted_date');
            $table->integer('days_until_purchase')->nullable();
            $table->decimal('confidence_score', 5, 4); // 0.0000 to 1.0000
            $table->json('predicted_categories')->nullable(); // What they'll likely buy
            $table->json('predicted_products')->nullable(); // Specific product recommendations
            $table->decimal('predicted_order_value', 10, 2)->nullable();
            $table->boolean('notification_sent')->default(false);
            $table->timestamp('notification_sent_at')->nullable();
            $table->boolean('prediction_accurate')->nullable(); // Set after actual purchase
            $table->timestamp('actual_purchase_at')->nullable();
            $table->timestamp('predicted_at');
            $table->timestamps();
            
            $table->index(['user_id', 'predicted_date']);
            $table->index(['predicted_date', 'notification_sent']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('next_purchase_predictions');
        Schema::dropIfExists('customer_lifetime_values');
        Schema::dropIfExists('churn_predictions');
        Schema::dropIfExists('rfm_scores');
        Schema::dropIfExists('customer_segment_memberships');
        Schema::dropIfExists('customer_segments');
    }
}
