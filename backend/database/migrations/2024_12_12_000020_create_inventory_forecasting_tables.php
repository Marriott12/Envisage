<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInventoryForecastingTables extends Migration
{
    public function up()
    {
        // Stock forecasts
        Schema::create('stock_forecasts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->date('forecast_date');
            $table->integer('predicted_demand')->default(0);
            $table->integer('current_stock')->default(0);
            $table->integer('recommended_stock')->default(0);
            $table->integer('reorder_quantity')->default(0);
            $table->decimal('confidence_score', 5, 4)->default(0);
            $table->json('factors'); // Seasonality, trends, promotions
            $table->boolean('stockout_risk')->default(false);
            $table->integer('days_until_stockout')->nullable();
            $table->timestamps();
            
            $table->index(['product_id', 'forecast_date']);
            $table->index('stockout_risk');
        });

        // Reorder points
        Schema::create('reorder_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->unique()->constrained('products')->onDelete('cascade');
            $table->integer('reorder_level')->default(0);
            $table->integer('safety_stock')->default(0);
            $table->integer('economic_order_quantity')->default(0);
            $table->integer('lead_time_days')->default(7);
            $table->decimal('avg_daily_sales', 8, 2)->default(0);
            $table->decimal('sales_variability', 5, 4)->default(0);
            $table->enum('criticality', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->timestamp('last_calculated_at')->nullable();
            $table->timestamps();
            
            $table->index('criticality');
        });

        // Purchase orders
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->string('po_number')->unique();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->onDelete('set null');
            $table->enum('status', ['draft', 'pending', 'approved', 'ordered', 'partial_received', 'received', 'cancelled'])->default('draft');
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->date('order_date');
            $table->date('expected_delivery_date')->nullable();
            $table->date('actual_delivery_date')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('auto_generated')->default(false);
            $table->timestamps();
            
            $table->index(['status', 'order_date']);
            $table->index('supplier_id');
        });

        // Purchase order items
        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained('purchase_orders')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->integer('quantity_ordered')->default(0);
            $table->integer('quantity_received')->default(0);
            $table->decimal('unit_cost', 10, 2)->default(0);
            $table->decimal('total_cost', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['purchase_order_id', 'product_id']);
        });

        // Supplier performance
        Schema::create('supplier_performance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->unique()->constrained('suppliers')->onDelete('cascade');
            $table->integer('total_orders')->default(0);
            $table->integer('on_time_deliveries')->default(0);
            $table->decimal('on_time_rate', 5, 4)->default(1.0000);
            $table->decimal('avg_lead_time_days', 5, 2)->default(0);
            $table->decimal('quality_score', 5, 4)->default(1.0000); // 0-1
            $table->decimal('avg_order_accuracy', 5, 4)->default(1.0000); // 0-1
            $table->integer('total_defects')->default(0);
            $table->decimal('defect_rate', 5, 4)->default(0);
            $table->enum('reliability_rating', ['poor', 'fair', 'good', 'excellent'])->default('good');
            $table->timestamp('last_order_date')->nullable();
            $table->timestamp('last_calculated_at')->nullable();
            $table->timestamps();
            
            $table->index('reliability_rating');
        });

        // Suppliers table (if not exists)
        if (!Schema::hasTable('suppliers')) {
            Schema::create('suppliers', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->nullable();
                $table->string('phone')->nullable();
                $table->text('address')->nullable();
                $table->string('contact_person')->nullable();
                $table->enum('status', ['active', 'inactive'])->default('active');
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        // Stock alerts
        Schema::create('stock_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->enum('alert_type', ['low_stock', 'stockout', 'overstock', 'reorder_needed', 'demand_spike']); 
            $table->enum('severity', ['info', 'warning', 'critical'])->default('warning');
            $table->string('message');
            $table->json('alert_data')->nullable();
            $table->boolean('resolved')->default(false);
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            $table->index(['product_id', 'resolved']);
            $table->index(['alert_type', 'severity']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('stock_alerts');
        Schema::dropIfExists('supplier_performance');
        Schema::dropIfExists('purchase_order_items');
        Schema::dropIfExists('purchase_orders');
        Schema::dropIfExists('reorder_points');
        Schema::dropIfExists('stock_forecasts');
        Schema::dropIfExists('suppliers');
    }
}
