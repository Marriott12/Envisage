<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAdditionalMarketplaceTables extends Migration
{
    public function up()
    {
        // Subscription Plans
        if (!Schema::hasTable('subscription_plans')) {
            Schema::create('subscription_plans', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->text('description')->nullable();
                $table->decimal('price', 10, 2);
                $table->integer('billing_interval_days')->default(30);
                $table->json('features')->nullable();
                $table->integer('max_listings')->nullable();
                $table->decimal('commission_rate', 5, 2)->nullable();
                $table->boolean('featured_listings')->default(false);
                $table->boolean('priority_support')->default(false);
                $table->boolean('analytics_access')->default(false);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // User Subscriptions
        if (!Schema::hasTable('subscriptions')) {
            Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('plan_id')->constrained('subscription_plans');
            $table->string('stripe_subscription_id')->nullable();
            $table->string('status'); // active, cancelled, expired, past_due
            $table->timestamp('current_period_start')->nullable();
            $table->timestamp('current_period_end')->nullable();
            $table->timestamp('next_billing_date')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            });
        }

        // Live Chat Conversations
        if (!Schema::hasTable('chat_conversations')) {
            Schema::create('chat_conversations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('buyer_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('seller_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('product_id')->nullable()->constrained()->onDelete('set null');
                $table->string('status')->default('active'); // active, closed
                $table->timestamp('closed_at')->nullable();
                $table->timestamps();

                $table->index(['buyer_id', 'status']);
                $table->index(['seller_id', 'status']);
                $table->index('product_id');
            });
        }

        // Chat Messages
        if (!Schema::hasTable('chat_messages')) {
            Schema::create('chat_messages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('conversation_id')->constrained('chat_conversations')->onDelete('cascade');
                $table->foreignId('sender_id')->constrained('users')->onDelete('cascade');
                $table->text('message');
                $table->string('attachment_url')->nullable();
                $table->boolean('is_read')->default(false);
                $table->timestamp('read_at')->nullable();
                $table->timestamps();

                $table->index(['conversation_id', 'created_at']);
                $table->index(['conversation_id', 'is_read']);
            });
        }

        // Shared Wishlists
        if (!Schema::hasTable('shared_wishlists')) {
            Schema::create('shared_wishlists', function (Blueprint $table) {
                $table->id();
                $table->foreignId('wishlist_id')->constrained()->onDelete('cascade');
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->string('share_token')->unique();
                $table->string('privacy')->default('public'); // public, private, friends
                $table->boolean('allow_comments')->default(true);
                $table->integer('views_count')->default(0);
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();

                $table->index('share_token');
                $table->index(['user_id', 'created_at']);
            });
        }

        // Flash Sales
        if (!Schema::hasTable('flash_sales')) {
            Schema::create('flash_sales', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->text('description')->nullable();
                $table->timestamp('starts_at');
                $table->timestamp('ends_at');
                $table->string('status')->default('scheduled'); // scheduled, active, ended
                $table->json('subscribers')->nullable();
                $table->timestamps();

                $table->index(['status', 'starts_at']);
                $table->index(['status', 'ends_at']);
            });
        }

        // Flash Sale Products
        if (!Schema::hasTable('flash_sale_products')) {
            Schema::create('flash_sale_products', function (Blueprint $table) {
                $table->id();
                $table->foreignId('flash_sale_id')->constrained()->onDelete('cascade');
                $table->foreignId('product_id')->constrained()->onDelete('cascade');
                $table->decimal('original_price', 10, 2);
                $table->decimal('sale_price', 10, 2);
                $table->decimal('discount_percentage', 5, 2);
                $table->integer('stock_limit');
                $table->integer('sold_count')->default(0);
                $table->timestamps();

                $table->index('flash_sale_id');
                $table->index('product_id');
            });
        }

        // Product Questions/Answers
        if (!Schema::hasTable('product_questions')) {
            Schema::create('product_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->text('question');
            $table->text('answer')->nullable();
            $table->foreignId('answered_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('answered_at')->nullable();
            $table->boolean('is_public')->default(true);
            $table->integer('helpful_count')->default(0);
            $table->timestamps();

                $table->index(['product_id', 'is_public']);
                $table->index(['user_id', 'created_at']);
            });
        }

        // Price Drop Alerts
        if (!Schema::hasTable('price_drop_alerts')) {
            Schema::create('price_drop_alerts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->foreignId('product_id')->constrained()->onDelete('cascade');
                $table->decimal('target_price', 10, 2);
                $table->decimal('original_price', 10, 2);
                $table->boolean('notified')->default(false);
                $table->timestamp('notified_at')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['product_id', 'is_active']);
                $table->index(['user_id', 'is_active']);
            });
        }

        // Bundle Deals
        if (!Schema::hasTable('bundle_deals')) {
            Schema::create('bundle_deals', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->text('description')->nullable();
                $table->decimal('regular_price', 10, 2);
                $table->decimal('bundle_price', 10, 2);
                $table->decimal('discount_percentage', 5, 2);
                $table->integer('stock')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamp('starts_at')->nullable();
                $table->timestamp('ends_at')->nullable();
                $table->timestamps();

                $table->index(['is_active', 'starts_at']);
            });
        }

        // Bundle Products
        if (!Schema::hasTable('bundle_products')) {
            Schema::create('bundle_products', function (Blueprint $table) {
                $table->id();
                $table->foreignId('bundle_id')->constrained('bundle_deals')->onDelete('cascade');
                $table->foreignId('product_id')->constrained()->onDelete('cascade');
                $table->integer('quantity')->default(1);
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('bundle_products');
        Schema::dropIfExists('bundle_deals');
        Schema::dropIfExists('price_drop_alerts');
        Schema::dropIfExists('product_questions');
        Schema::dropIfExists('flash_sale_products');
        Schema::dropIfExists('flash_sales');
        Schema::dropIfExists('shared_wishlists');
        Schema::dropIfExists('chat_messages');
        Schema::dropIfExists('chat_conversations');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('subscription_plans');
    }
}
