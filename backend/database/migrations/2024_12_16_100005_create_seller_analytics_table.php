<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSellerAnalyticsTable extends Migration
{
    public function up()
    {
        // Seller performance metrics (daily snapshots)
        Schema::create('seller_analytics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_id')->constrained('users')->onDelete('cascade');
            $table->date('date');
            $table->integer('orders_count')->default(0);
            $table->decimal('revenue', 12, 2)->default(0);
            $table->decimal('avg_order_value', 10, 2)->default(0);
            $table->integer('products_sold')->default(0);
            $table->integer('new_customers')->default(0);
            $table->integer('returning_customers')->default(0);
            $table->integer('refunds_count')->default(0);
            $table->decimal('refunds_amount', 10, 2)->default(0);
            $table->decimal('rating_average', 3, 2)->default(0);
            $table->integer('reviews_count')->default(0);
            $table->integer('views_count')->default(0);
            $table->decimal('conversion_rate', 5, 2)->default(0);
            $table->timestamps();
            
            $table->unique(['seller_id', 'date']);
            $table->index(['seller_id', 'date']);
        });

        // Product performance by seller
        Schema::create('seller_product_analytics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->date('date');
            $table->integer('views_count')->default(0);
            $table->integer('cart_adds')->default(0);
            $table->integer('purchases')->default(0);
            $table->integer('quantity_sold')->default(0);
            $table->decimal('revenue', 10, 2)->default(0);
            $table->decimal('conversion_rate', 5, 2)->default(0);
            $table->timestamps();
            
            $table->unique(['seller_id', 'product_id', 'date']);
            $table->index(['seller_id', 'date']);
        });

        // Seller revenue forecasts
        Schema::create('seller_revenue_forecasts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_id')->constrained('users')->onDelete('cascade');
            $table->date('forecast_date');
            $table->string('period'); // daily, weekly, monthly
            $table->decimal('predicted_revenue', 12, 2);
            $table->decimal('actual_revenue', 12, 2)->nullable();
            $table->decimal('confidence_score', 5, 2);
            $table->json('factors')->nullable(); // seasonality, trends, etc.
            $table->timestamps();
            
            $table->index(['seller_id', 'forecast_date']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('seller_revenue_forecasts');
        Schema::dropIfExists('seller_product_analytics');
        Schema::dropIfExists('seller_analytics');
    }
}
