<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAiAnalyticsTables extends Migration
{
    /**
     * Run the migrations - Create AI Analytics & Tracking Tables
     * Enterprise-grade AI performance monitoring for Envisage AI Platform v2.0
     *
     * @return void
     */
    public function up()
    {
        // AI Metrics - Track all AI service usage and performance
        if (!Schema::hasTable('ai_metrics')) {
            Schema::create('ai_metrics', function (Blueprint $table) {
                $table->id();
                $table->string('service'); // recommendations, chatbot, visual_search, etc.
                $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
                $table->string('endpoint'); // API endpoint called
                $table->integer('response_time_ms'); // Response time
                $table->boolean('success')->default(true);
                $table->text('error_message')->nullable();
                $table->integer('tokens_used')->nullable(); // For GPT-4, etc.
                $table->decimal('cost_usd', 10, 4)->nullable(); // Cost per request
                $table->json('metadata')->nullable(); // Additional metrics
                $table->timestamps();
                
                $table->index(['service', 'created_at'], 'idx_metrics_service_date');
            });
        }

        // AI Costs - Daily cost tracking
        if (!Schema::hasTable('ai_costs')) {
            Schema::create('ai_costs', function (Blueprint $table) {
                $table->id();
                $table->string('service');
                $table->date('date');
                $table->integer('total_requests');
                $table->integer('successful_requests');
                $table->integer('failed_requests');
                $table->bigInteger('total_tokens')->nullable();
                $table->decimal('total_cost_usd', 10, 2);
                $table->decimal('avg_response_time_ms', 10, 2);
                $table->timestamps();
                
                $table->unique(['service', 'date']);
            });
        }

        // Recommendation Clicks - Track CTR
        if (!Schema::hasTable('recommendation_clicks')) {
            Schema::create('recommendation_clicks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
                $table->foreignId('product_id')->constrained()->onDelete('cascade');
                $table->string('algorithm'); // neural, bandit, session, context
                $table->decimal('confidence_score', 5, 4);
                $table->integer('position'); // Position in recommendation list
                $table->boolean('clicked')->default(false);
                $table->boolean('purchased')->default(false);
                $table->timestamp('clicked_at')->nullable();
                $table->timestamps();
                
                $table->index(['algorithm', 'clicked']);
            });
        }

        // Chatbot Conversations - Track bot performance
        if (!Schema::hasTable('chatbot_conversations')) {
            Schema::create('chatbot_conversations', function (Blueprint $table) {
                $table->id();
                $table->string('conversation_id')->unique();
                $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
                $table->string('intent')->nullable();
                $table->integer('message_count')->default(0);
                $table->boolean('resolved')->default(false);
                $table->integer('resolution_time_seconds')->nullable();
                $table->decimal('satisfaction_score', 3, 2)->nullable();
                $table->boolean('escalated_to_human')->default(false);
                $table->integer('tokens_used')->default(0);
                $table->decimal('cost_usd', 10, 4)->default(0);
                $table->timestamps();
                
                $table->index(['intent', 'resolved']);
            });
        }

        // Visual Search History
        if (!Schema::hasTable('visual_searches')) {
            Schema::create('visual_searches', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
                $table->string('image_hash');
                $table->string('image_path');
                $table->integer('results_count');
                $table->decimal('avg_similarity_score', 5, 4);
                $table->boolean('clicked_result')->default(false);
                $table->integer('processing_time_ms');
                $table->json('dominant_colors')->nullable();
                $table->timestamps();
            });
        }

        // Content Generation History
        if (!Schema::hasTable('generated_content')) {
            Schema::create('generated_content', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->string('content_type');
                $table->string('tone');
                $table->string('length');
                $table->text('prompt');
                $table->text('generated_text');
                $table->integer('tokens_used');
                $table->decimal('cost_usd', 10, 4);
                $table->boolean('approved')->nullable();
                $table->boolean('used')->default(false);
                $table->timestamps();
            });
        }

        // A/B Test Experiments
        if (!Schema::hasTable('ab_experiments')) {
            Schema::create('ab_experiments', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('type');
                $table->text('description')->nullable();
                $table->json('variants');
                $table->json('traffic_split');
                $table->string('status')->default('draft');
                $table->date('start_date')->nullable();
                $table->date('end_date')->nullable();
                $table->string('primary_metric');
                $table->string('winning_variant')->nullable();
                $table->timestamps();
            });
        }

        // A/B Test Results
        if (!Schema::hasTable('ab_test_results')) {
            Schema::create('ab_test_results', function (Blueprint $table) {
                $table->id();
                $table->foreignId('experiment_id')->constrained('ab_experiments')->onDelete('cascade');
                $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
                $table->string('variant');
                $table->string('metric_name');
                $table->decimal('metric_value', 10, 4);
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }

        // Fraud Detection Decisions
        if (!Schema::hasTable('fraud_decisions')) {
            Schema::create('fraud_decisions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('fraud_alert_id')->constrained()->onDelete('cascade');
                $table->foreignId('reviewed_by')->nullable()->constrained('users')->onDelete('set null');
                $table->string('decision');
                $table->text('notes')->nullable();
                $table->boolean('was_correct')->nullable();
                $table->timestamps();
            });
        }

        // Sentiment Analysis Cache
        if (!Schema::hasTable('sentiment_cache')) {
            Schema::create('sentiment_cache', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained()->onDelete('cascade');
                $table->decimal('overall_sentiment', 3, 2);
                $table->json('sentiment_distribution');
                $table->text('ai_summary');
                $table->json('key_themes');
                $table->json('aspect_sentiments')->nullable();
                $table->integer('review_count');
                $table->integer('fake_review_count')->default(0);
                $table->timestamp('last_analyzed_at');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sentiment_cache');
        Schema::dropIfExists('fraud_decisions');
        Schema::dropIfExists('ab_test_results');
        Schema::dropIfExists('ab_experiments');
        Schema::dropIfExists('generated_content');
        Schema::dropIfExists('visual_searches');
        Schema::dropIfExists('chatbot_conversations');
        Schema::dropIfExists('recommendation_clicks');
        Schema::dropIfExists('ai_costs');
        Schema::dropIfExists('ai_metrics');
    }
}
