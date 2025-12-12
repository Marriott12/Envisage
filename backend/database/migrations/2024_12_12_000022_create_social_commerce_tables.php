<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSocialCommerceTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Social Commerce Products - Track products synced to social platforms
        Schema::create('social_commerce_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->enum('platform', ['instagram', 'facebook', 'tiktok']); 
            $table->string('platform_product_id')->nullable(); // External ID
            $table->enum('status', ['pending', 'active', 'rejected', 'removed'])->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamp('last_updated_at')->nullable();
            $table->json('platform_data')->nullable(); // Platform-specific data
            $table->timestamps();
            
            $table->unique(['product_id', 'platform']);
            $table->index('platform');
            $table->index('status');
        });

        // Social Commerce Orders - Track orders from social platforms
        Schema::create('social_commerce_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->nullable()->constrained()->onDelete('set null');
            $table->enum('platform', ['instagram', 'facebook', 'tiktok']);
            $table->string('platform_order_id')->unique(); // External order ID
            $table->string('customer_name');
            $table->string('customer_email');
            $table->string('customer_phone')->nullable();
            $table->text('shipping_address');
            $table->decimal('total_amount', 10, 2);
            $table->string('currency', 3)->default('USD');
            $table->enum('status', ['pending', 'imported', 'completed', 'cancelled', 'failed'])->default('pending');
            $table->text('platform_data')->nullable(); // JSON - Raw order data
            $table->timestamp('platform_created_at')->nullable();
            $table->timestamp('imported_at')->nullable();
            $table->timestamps();
            
            $table->index('platform');
            $table->index('status');
            $table->index('platform_order_id');
        });

        // Social Commerce Sync Logs - Track all sync operations
        Schema::create('social_commerce_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->enum('platform', ['instagram', 'facebook', 'tiktok']);
            $table->enum('sync_type', ['products', 'inventory', 'orders', 'catalog']); 
            $table->enum('direction', ['export', 'import']); 
            $table->enum('status', ['pending', 'in_progress', 'completed', 'failed'])->default('pending');
            $table->integer('items_total')->default(0);
            $table->integer('items_processed')->default(0);
            $table->integer('items_successful')->default(0);
            $table->integer('items_failed')->default(0);
            $table->text('error_message')->nullable();
            $table->json('summary')->nullable(); // Details about what was synced
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            
            $table->index('platform');
            $table->index('sync_type');
            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('social_commerce_sync_logs');
        Schema::dropIfExists('social_commerce_orders');
        Schema::dropIfExists('social_commerce_products');
    }
}
