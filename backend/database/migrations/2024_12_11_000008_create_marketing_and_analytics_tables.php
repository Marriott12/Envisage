<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMarketingAndAnalyticsTables extends Migration
{
    public function up()
    {
        // Affiliate Program
        Schema::create('affiliates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('affiliate_code')->unique();
            $table->decimal('commission_rate', 5, 2)->default(5.00); // percentage
            $table->decimal('total_earnings', 10, 2)->default(0);
            $table->decimal('pending_earnings', 10, 2)->default(0);
            $table->decimal('paid_earnings', 10, 2)->default(0);
            $table->integer('total_referrals')->default(0);
            $table->integer('total_sales')->default(0);
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');
            $table->string('payment_method')->nullable();
            $table->json('payment_details')->nullable();
            $table->timestamps();
            
            $table->index('affiliate_code');
            $table->index(['user_id', 'status']);
        });

        Schema::create('affiliate_conversions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affiliate_id')->constrained()->onDelete('cascade');
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('customer_id')->constrained('users')->onDelete('cascade');
            $table->decimal('order_amount', 10, 2);
            $table->decimal('commission_amount', 10, 2);
            $table->decimal('commission_rate', 5, 2);
            $table->enum('status', ['pending', 'approved', 'paid', 'cancelled'])->default('pending');
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            
            $table->index(['affiliate_id', 'status']);
            $table->index('order_id');
        });

        // Recently Viewed Products
        Schema::create('recently_viewed', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('session_id')->nullable();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->timestamp('viewed_at')->useCurrent();
            
            $table->index(['user_id', 'viewed_at']);
            $table->index(['session_id', 'viewed_at']);
            $table->index('product_id');
        });

        // Search History
        Schema::create('search_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('session_id')->nullable();
            $table->string('query');
            $table->integer('results_count')->default(0);
            $table->json('filters')->nullable();
            $table->boolean('clicked_result')->default(false);
            $table->foreignId('clicked_product_id')->nullable()->constrained('products')->onDelete('set null');
            $table->timestamps();
            
            $table->index(['user_id', 'created_at']);
            $table->index('query');
        });

        // Email Campaigns
        Schema::create('email_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('subject');
            $table->text('content');
            $table->enum('type', ['newsletter', 'promotional', 'transactional', 'abandoned_cart']);
            $table->enum('status', ['draft', 'scheduled', 'sending', 'sent', 'cancelled'])->default('draft');
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->integer('recipients_count')->default(0);
            $table->integer('sent_count')->default(0);
            $table->integer('opened_count')->default(0);
            $table->integer('clicked_count')->default(0);
            $table->json('segment_filters')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
            
            $table->index(['status', 'scheduled_at']);
        });

        // Two-Factor Authentication
        Schema::create('two_factor_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('code');
            $table->enum('method', ['email', 'sms', 'app'])->default('email');
            $table->timestamp('expires_at');
            $table->boolean('is_used')->default(false);
            $table->timestamps();
            
            $table->index(['user_id', 'code', 'is_used']);
            $table->index('expires_at');
        });

        // Security Audit Log
        Schema::create('security_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('action'); // login, logout, password_change, 2fa_enabled, etc
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->enum('status', ['success', 'failed', 'blocked'])->default('success');
            $table->text('reason')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'created_at']);
            $table->index(['action', 'status']);
            $table->index('ip_address');
        });
    }

    public function down()
    {
        Schema::dropIfExists('security_logs');
        Schema::dropIfExists('two_factor_codes');
        Schema::dropIfExists('email_campaigns');
        Schema::dropIfExists('search_history');
        Schema::dropIfExists('recently_viewed');
        Schema::dropIfExists('affiliate_conversions');
        Schema::dropIfExists('affiliates');
    }
}
