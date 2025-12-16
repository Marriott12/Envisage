<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStockAlertsTable extends Migration
{
    public function up()
    {
        // Stock alert subscriptions
        Schema::create('stock_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->boolean('email_sent')->default(false);
            $table->boolean('sms_sent')->default(false);
            $table->boolean('push_sent')->default(false);
            $table->timestamp('notified_at')->nullable();
            $table->string('status')->default('active'); // active, notified, cancelled
            $table->timestamps();
            
            $table->index(['product_id', 'status']);
            $table->index(['user_id', 'product_id']);
        });

        // Stock threshold alerts for sellers
        Schema::create('stock_threshold_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('seller_id')->constrained('users')->onDelete('cascade');
            $table->integer('threshold_quantity')->default(5);
            $table->boolean('enabled')->default(true);
            $table->timestamp('last_alert_sent')->nullable();
            $table->timestamps();
            
            $table->unique(['product_id', 'seller_id']);
        });

        // Stock movement history
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->integer('quantity_before');
            $table->integer('quantity_after');
            $table->integer('quantity_change');
            $table->string('type'); // sale, restock, adjustment, return, damaged
            $table->string('reason')->nullable();
            $table->foreignId('order_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['product_id', 'created_at']);
            $table->index('type');
        });
    }

    public function down()
    {
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('stock_threshold_alerts');
        Schema::dropIfExists('stock_alerts');
    }
}
