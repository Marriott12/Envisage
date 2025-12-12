<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFraudDetectionTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Fraud rules for detecting suspicious patterns
        Schema::create('fraud_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('rule_type', [
                'velocity_check',
                'amount_threshold',
                'location_mismatch',
                'device_fingerprint',
                'behavioral_pattern',
                'blacklist_match',
                'high_risk_country',
                'suspicious_email',
                'multiple_cards',
                'unusual_time'
            ]);
            $table->json('conditions'); // Rule conditions
            $table->integer('risk_score'); // Points added if triggered (0-100)
            $table->enum('action', ['flag', 'review', 'block'])->default('flag');
            $table->boolean('is_active')->default(true);
            $table->integer('priority')->default(0); // Higher = checked first
            $table->integer('trigger_count')->default(0); // How many times triggered
            $table->timestamps();
            
            $table->index(['is_active', 'priority']);
            $table->index('rule_type');
        });

        // Fraud scores for orders/transactions
        Schema::create('fraud_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->integer('total_score')->default(0); // 0-100 risk score
            $table->enum('risk_level', ['low', 'medium', 'high', 'critical'])->default('low');
            $table->json('triggered_rules'); // Array of rule IDs that triggered
            $table->json('score_breakdown'); // Detailed scoring
            $table->json('analysis_data'); // IP, device, location, etc.
            $table->enum('status', ['pending', 'approved', 'rejected', 'under_review'])->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->boolean('false_positive')->default(false);
            $table->timestamps();
            
            $table->index(['order_id', 'risk_level']);
            $table->index(['user_id', 'created_at']);
            $table->index('status');
            $table->index('risk_level');
        });

        // Fraud attempts tracking
        Schema::create('fraud_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('order_id')->nullable()->constrained('orders')->onDelete('set null');
            $table->enum('attempt_type', [
                'multiple_failed_payments',
                'card_testing',
                'account_takeover',
                'promo_abuse',
                'refund_fraud',
                'friendly_fraud',
                'identity_theft',
                'bot_activity',
                'credential_stuffing'
            ]);
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->string('device_fingerprint')->nullable();
            $table->json('attempt_data'); // Detailed attempt information
            $table->integer('severity')->default(1); // 1-10
            $table->boolean('blocked')->default(false);
            $table->text('block_reason')->nullable();
            $table->timestamps();
            
            $table->index(['ip_address', 'created_at']);
            $table->index(['user_id', 'attempt_type']);
            $table->index('device_fingerprint');
            $table->index(['blocked', 'severity']);
        });

        // Blacklist for IPs, emails, cards, devices
        Schema::create('blacklist', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['ip', 'email', 'card_hash', 'device', 'phone', 'address_hash', 'user_id']);
            $table->string('value'); // The blacklisted value (hashed for sensitive data)
            $table->string('reason');
            $table->enum('severity', ['low', 'medium', 'high', 'permanent'])->default('medium');
            $table->timestamp('expires_at')->nullable(); // Temporary bans
            $table->boolean('is_active')->default(true);
            $table->integer('hit_count')->default(0); // Times triggered
            $table->foreignId('added_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['type', 'value', 'is_active']);
            $table->index(['expires_at', 'is_active']);
            $table->index('severity');
        });

        // Velocity tracking (for rate limiting)
        Schema::create('velocity_tracking', function (Blueprint $table) {
            $table->id();
            $table->string('identifier', 100); // user_id, ip, device, email, card_hash
            $table->string('identifier_type', 20); // 'user', 'ip', 'device', 'email', 'card'
            $table->string('action', 50); // 'order', 'payment', 'login', 'card_add'
            $table->integer('count')->default(1);
            $table->timestamp('window_start');
            $table->timestamp('window_end');
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index(['identifier', 'identifier_type', 'action'], 'vel_track_idx');
            $table->index(['window_end']); // For cleanup
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('velocity_tracking');
        Schema::dropIfExists('blacklist');
        Schema::dropIfExists('fraud_attempts');
        Schema::dropIfExists('fraud_scores');
        Schema::dropIfExists('fraud_rules');
    }
}
