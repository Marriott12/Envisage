<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMarketingAutomationTables extends Migration
{
    public function up()
    {
        // Email Templates
        if (!Schema::hasTable('email_templates')) {
            Schema::create('email_templates', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('subject');
                $table->text('body');
                $table->string('type'); // transactional, marketing, automation
                $table->json('variables')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // Marketing Campaigns
        if (!Schema::hasTable('campaigns')) {
            Schema::create('campaigns', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('type'); // email, sms, push
                $table->text('description')->nullable();
                $table->foreignId('template_id')->nullable()->constrained('email_templates')->onDelete('set null');
                $table->string('status')->default('draft'); // draft, scheduled, active, completed, paused
                $table->json('target_audience')->nullable();
                $table->timestamp('scheduled_at')->nullable();
                $table->timestamp('sent_at')->nullable();
                $table->integer('total_sent')->default(0);
                $table->integer('opened')->default(0);
                $table->integer('clicked')->default(0);
                $table->integer('converted')->default(0);
                $table->decimal('conversion_rate', 5, 2)->default(0);
                $table->timestamps();
                
                $table->index(['status', 'scheduled_at']);
            });
        }

        // Campaign Logs
        if (!Schema::hasTable('campaign_logs')) {
            Schema::create('campaign_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('campaign_id')->constrained()->onDelete('cascade');
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->string('status'); // sent, opened, clicked, bounced, unsubscribed
                $table->timestamp('sent_at')->nullable();
                $table->timestamp('opened_at')->nullable();
                $table->timestamp('clicked_at')->nullable();
                $table->string('user_agent')->nullable();
                $table->string('ip_address')->nullable();
                $table->timestamps();
                
                $table->index(['campaign_id', 'status']);
                $table->index(['user_id', 'created_at']);
            });
        }

        // Automation Rules
        if (!Schema::hasTable('automation_rules')) {
            Schema::create('automation_rules', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('trigger'); // cart_abandoned, user_registered, order_completed, etc.
                $table->json('conditions')->nullable();
                $table->json('actions'); // send_email, send_sms, add_tag, etc.
                $table->integer('delay_minutes')->default(0);
                $table->boolean('is_active')->default(true);
                $table->integer('executions_count')->default(0);
                $table->timestamps();
            });
        }

        // Automation Executions
        if (!Schema::hasTable('automation_executions')) {
            Schema::create('automation_executions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('rule_id')->constrained('automation_rules')->onDelete('cascade');
                $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
                $table->string('status'); // pending, executed, failed
                $table->json('data')->nullable();
                $table->timestamp('scheduled_at');
                $table->timestamp('executed_at')->nullable();
                $table->text('error_message')->nullable();
                $table->timestamps();
                
                $table->index(['rule_id', 'status']);
                $table->index(['scheduled_at', 'status']);
            });
        }

        // Abandoned Carts Tracking
        if (!Schema::hasTable('abandoned_carts')) {
            Schema::create('abandoned_carts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->json('cart_data');
                $table->decimal('total_amount', 10, 2);
                $table->timestamp('abandoned_at');
                $table->boolean('recovery_email_sent')->default(false);
                $table->timestamp('recovered_at')->nullable();
                $table->foreignId('recovered_order_id')->nullable()->constrained('orders')->onDelete('set null');
                $table->timestamps();
                
                $table->index(['user_id', 'abandoned_at']);
                $table->index(['recovery_email_sent', 'abandoned_at']);
            });
        }

        // SMS Campaigns
        if (!Schema::hasTable('sms_campaigns')) {
            Schema::create('sms_campaigns', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->text('message');
                $table->string('status')->default('draft');
                $table->json('target_phones')->nullable();
                $table->timestamp('scheduled_at')->nullable();
                $table->integer('total_sent')->default(0);
                $table->integer('delivered')->default(0);
                $table->integer('failed')->default(0);
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('sms_campaigns');
        Schema::dropIfExists('abandoned_carts');
        Schema::dropIfExists('automation_executions');
        Schema::dropIfExists('automation_rules');
        Schema::dropIfExists('campaign_logs');
        Schema::dropIfExists('campaigns');
        Schema::dropIfExists('email_templates');
    }
}
