<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAdvancedFeaturesTablesOne extends Migration
{
    public function up()
    {
        // Gift Cards
        Schema::create('gift_cards', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->decimal('initial_amount', 10, 2);
            $table->decimal('balance', 10, 2);
            $table->foreignId('purchased_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('used_by')->nullable()->constrained('users')->onDelete('set null');
            $table->string('recipient_email')->nullable();
            $table->text('message')->nullable();
            $table->date('expires_at')->nullable();
            $table->enum('status', ['active', 'used', 'expired', 'cancelled'])->default('active');
            $table->timestamps();
            
            $table->index('code');
            $table->index(['status', 'expires_at']);
        });

        // Vouchers/Coupons
        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('type', ['percentage', 'fixed', 'free_shipping']);
            $table->decimal('value', 10, 2);
            $table->decimal('min_purchase_amount', 10, 2)->nullable();
            $table->decimal('max_discount', 10, 2)->nullable();
            $table->integer('usage_limit')->nullable();
            $table->integer('usage_count')->default(0);
            $table->integer('per_user_limit')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('applicable_products')->nullable();
            $table->json('applicable_categories')->nullable();
            $table->timestamps();
            
            $table->index('code');
            $table->index(['is_active', 'expires_at']);
        });

        Schema::create('voucher_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('voucher_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->decimal('discount_amount', 10, 2);
            $table->timestamps();
            
            $table->index(['voucher_id', 'user_id']);
        });

        // Product Comparisons
        Schema::create('product_comparisons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->json('product_ids');
            $table->string('name')->nullable();
            $table->timestamps();
            
            $table->index('user_id');
        });

        // Video Reviews
        Schema::create('video_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('review_id')->constrained()->onDelete('cascade');
            $table->string('video_url');
            $table->string('thumbnail_url')->nullable();
            $table->integer('duration')->nullable(); // in seconds
            $table->string('provider')->default('local'); // local, youtube, vimeo
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index('review_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('video_reviews');
        Schema::dropIfExists('product_comparisons');
        Schema::dropIfExists('voucher_usages');
        Schema::dropIfExists('vouchers');
        Schema::dropIfExists('gift_cards');
    }
}
