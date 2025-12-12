<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReferralProgramTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Referral Tiers (Bronze, Silver, Gold, Platinum)
        Schema::create('referral_tiers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50); // Bronze, Silver, Gold, Platinum
            $table->integer('min_referrals')->default(0); // Minimum referrals to reach tier
            $table->integer('max_referrals')->nullable(); // Maximum referrals (null = unlimited)
            $table->decimal('commission_rate', 5, 2)->default(5.00); // % of referee's first purchase
            $table->decimal('bonus_amount', 10, 2)->default(0); // Bonus for reaching tier
            $table->json('benefits')->nullable(); // Additional benefits (free shipping, etc.)
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Referrals - Track referrer-referee relationships
        Schema::create('referrals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referrer_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('referee_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->string('referee_email')->nullable(); // Email before registration
            $table->string('referee_name')->nullable();
            $table->string('status', 20)->default('pending'); // pending, registered, converted, expired
            $table->timestamp('registered_at')->nullable();
            $table->timestamp('converted_at')->nullable(); // First purchase
            $table->timestamp('expires_at')->nullable(); // Link expiration (90 days)
            $table->string('referral_code', 20)->unique();
            $table->string('source')->nullable(); // email, social, direct
            $table->json('metadata')->nullable(); // UTM params, campaign info
            $table->timestamps();
            
            $table->index(['referrer_id', 'status']);
            $table->index('referral_code');
        });

        // Referral Links - Unique shareable links
        Schema::create('referral_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('token', 32)->unique();
            $table->string('url', 500);
            $table->integer('clicks')->default(0);
            $table->integer('conversions')->default(0);
            $table->decimal('conversion_rate', 5, 2)->default(0);
            $table->string('campaign_name')->nullable();
            $table->json('utm_params')->nullable(); // UTM tracking
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_clicked_at')->nullable();
            $table->timestamps();
            
            $table->index('token');
            $table->index('user_id');
        });

        // Referral Rewards - Track all rewards earned
        Schema::create('referral_rewards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referrer_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('referral_id')->constrained('referrals')->onDelete('cascade');
            $table->string('reward_type', 20); // commission, bonus, credit
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('USD');
            $table->string('status', 20)->default('pending'); // pending, approved, paid, cancelled
            $table->foreignId('order_id')->nullable()->constrained('orders')->onDelete('set null');
            $table->text('description')->nullable();
            $table->timestamp('earned_at');
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            
            $table->index(['referrer_id', 'status']);
            $table->index('status');
        });

        // Referral Conversions - Track conversion events
        Schema::create('referral_conversions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referral_id')->constrained('referrals')->onDelete('cascade');
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->decimal('order_amount', 10, 2);
            $table->decimal('commission_amount', 10, 2);
            $table->decimal('commission_rate', 5, 2);
            $table->boolean('is_first_purchase')->default(false);
            $table->integer('days_to_convert')->nullable(); // Days from registration to purchase
            $table->timestamps();
            
            $table->index('referral_id');
            $table->index('order_id');
        });

        // Referral Analytics - Daily aggregated stats
        Schema::create('referral_analytics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->date('date');
            $table->integer('total_referrals')->default(0);
            $table->integer('pending_referrals')->default(0);
            $table->integer('registered_referrals')->default(0);
            $table->integer('converted_referrals')->default(0);
            $table->integer('link_clicks')->default(0);
            $table->decimal('total_commission', 10, 2)->default(0);
            $table->decimal('pending_commission', 10, 2)->default(0);
            $table->decimal('paid_commission', 10, 2)->default(0);
            $table->decimal('conversion_rate', 5, 2)->default(0);
            $table->integer('current_tier_id')->nullable();
            $table->timestamps();
            
            $table->unique(['user_id', 'date']);
            $table->index('date');
        });

        // Seed default tiers
        DB::table('referral_tiers')->insert([
            [
                'name' => 'Bronze',
                'min_referrals' => 0,
                'max_referrals' => 4,
                'commission_rate' => 5.00,
                'bonus_amount' => 0,
                'benefits' => json_encode(['badge' => 'Bronze Referrer']),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Silver',
                'min_referrals' => 5,
                'max_referrals' => 14,
                'commission_rate' => 7.50,
                'bonus_amount' => 50.00,
                'benefits' => json_encode(['badge' => 'Silver Referrer', 'priority_support' => true]),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Gold',
                'min_referrals' => 15,
                'max_referrals' => 49,
                'commission_rate' => 10.00,
                'bonus_amount' => 150.00,
                'benefits' => json_encode(['badge' => 'Gold Referrer', 'priority_support' => true, 'exclusive_deals' => true]),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Platinum',
                'min_referrals' => 50,
                'max_referrals' => null,
                'commission_rate' => 15.00,
                'bonus_amount' => 500.00,
                'benefits' => json_encode(['badge' => 'Platinum Referrer', 'priority_support' => true, 'exclusive_deals' => true, 'personal_account_manager' => true]),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('referral_analytics');
        Schema::dropIfExists('referral_conversions');
        Schema::dropIfExists('referral_rewards');
        Schema::dropIfExists('referral_links');
        Schema::dropIfExists('referrals');
        Schema::dropIfExists('referral_tiers');
    }
}
