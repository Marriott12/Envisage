<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePreOrdersTable extends Migration
{
    public function up()
    {
        // Add pre-order fields to products
        if (!Schema::hasColumn('products', 'is_preorder')) {
            Schema::table('products', function (Blueprint $table) {
                $table->boolean('is_preorder')->default(false)->after('stock_quantity');
                $table->date('expected_ship_date')->nullable()->after('is_preorder');
                $table->integer('preorder_limit')->nullable()->after('expected_ship_date');
                $table->boolean('charge_now')->default(false)->after('preorder_limit');
            });
        }

        // Pre-order reservations
        Schema::create('pre_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->integer('quantity');
            $table->decimal('price_per_unit', 10, 2);
            $table->decimal('total_amount', 10, 2);
            $table->boolean('deposit_paid')->default(false);
            $table->decimal('deposit_amount', 10, 2)->default(0);
            $table->string('status')->default('reserved'); // reserved, charged, shipped, cancelled
            $table->date('expected_ship_date')->nullable();
            $table->foreignId('order_id')->nullable()->constrained()->onDelete('set null');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['product_id', 'status']);
            $table->index(['user_id', 'status']);
        });

        // Pre-order notifications
        Schema::create('preorder_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pre_order_id')->constrained()->onDelete('cascade');
            $table->string('type'); // shipment_delayed, ready_to_ship, shipped, arrival_soon
            $table->text('message');
            $table->boolean('sent')->default(false);
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('preorder_notifications');
        Schema::dropIfExists('pre_orders');
        
        if (Schema::hasColumn('products', 'is_preorder')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn(['is_preorder', 'expected_ship_date', 'preorder_limit', 'charge_now']);
            });
        }
    }
}
