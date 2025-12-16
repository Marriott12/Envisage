<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFlashSalesTable extends Migration
{
    public function up()
    {
        // Flash sale campaigns
        Schema::create('flash_sales', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('slug')->unique();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->boolean('active')->default(true);
            $table->integer('max_quantity_per_user')->default(5);
            $table->string('banner_image')->nullable();
            $table->integer('priority')->default(0);
            $table->timestamps();
            
            $table->index(['active', 'starts_at', 'ends_at']);
        });

        // Products in flash sale
        Schema::create('flash_sale_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('flash_sale_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->decimal('original_price', 10, 2);
            $table->decimal('sale_price', 10, 2);
            $table->decimal('discount_percentage', 5, 2);
            $table->integer('quantity_available');
            $table->integer('quantity_sold')->default(0);
            $table->integer('quantity_per_user')->default(2);
            $table->boolean('active')->default(true);
            $table->timestamps();
            
            $table->unique(['flash_sale_id', 'product_id']);
            $table->index(['product_id', 'active']);
        });

        // User flash sale purchases (track limits)
        Schema::create('flash_sale_user_purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('flash_sale_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->integer('quantity_purchased');
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->timestamps();
            
            $table->index(['flash_sale_id', 'user_id', 'product_id']);
        });

        // Daily deals (one per day)
        Schema::create('daily_deals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->date('deal_date');
            $table->decimal('original_price', 10, 2);
            $table->decimal('deal_price', 10, 2);
            $table->decimal('discount_percentage', 5, 2);
            $table->integer('quantity_available');
            $table->integer('quantity_sold')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();
            
            $table->unique(['product_id', 'deal_date']);
            $table->index('deal_date');
        });
    }

    public function down()
    {
        Schema::dropIfExists('daily_deals');
        Schema::dropIfExists('flash_sale_user_purchases');
        Schema::dropIfExists('flash_sale_products');
        Schema::dropIfExists('flash_sales');
    }
}
