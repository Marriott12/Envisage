<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAdvancedFeaturesTablesTwo extends Migration
{
    public function up()
    {
        // Make an Offer / Negotiations
        Schema::create('offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('buyer_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('seller_id')->constrained('users')->onDelete('cascade');
            $table->decimal('offered_price', 10, 2);
            $table->decimal('counter_price', 10, 2)->nullable();
            $table->enum('status', ['pending', 'countered', 'accepted', 'rejected', 'expired'])->default('pending');
            $table->text('buyer_message')->nullable();
            $table->text('seller_message')->nullable();
            $table->timestamp('expires_at');
            $table->timestamps();
            
            $table->index(['product_id', 'status']);
            $table->index(['buyer_id', 'status']);
            $table->index('seller_id');
        });

        // Auctions
        Schema::create('auctions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('seller_id')->constrained('users')->onDelete('cascade');
            $table->decimal('starting_price', 10, 2);
            $table->decimal('reserve_price', 10, 2)->nullable();
            $table->decimal('current_price', 10, 2);
            $table->decimal('buy_now_price', 10, 2)->nullable();
            $table->integer('bid_count')->default(0);
            $table->foreignId('highest_bidder_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->enum('status', ['scheduled', 'active', 'ended', 'cancelled'])->default('scheduled');
            $table->boolean('reserve_met')->default(false);
            $table->timestamps();
            
            $table->index(['status', 'ends_at']);
            $table->index('product_id');
        });

        Schema::create('auction_bids', function (Blueprint $table) {
            $table->id();
            $table->foreignId('auction_id')->constrained()->onDelete('cascade');
            $table->foreignId('bidder_id')->constrained('users')->onDelete('cascade');
            $table->decimal('bid_amount', 10, 2);
            $table->boolean('is_winning')->default(false);
            $table->boolean('is_autobid')->default(false);
            $table->decimal('max_autobid_amount', 10, 2)->nullable();
            $table->timestamps();
            
            $table->index(['auction_id', 'is_winning']);
            $table->index('bidder_id');
        });

        // Live Chat Support
        Schema::create('support_tickets', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_number')->unique();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');
            $table->string('subject');
            $table->text('description');
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->enum('status', ['open', 'in_progress', 'waiting', 'resolved', 'closed'])->default('open');
            $table->enum('category', ['general', 'order', 'payment', 'shipping', 'product', 'account', 'technical'])->default('general');
            $table->foreignId('order_id')->nullable()->constrained()->onDelete('set null');
            $table->timestamp('first_response_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'status']);
            $table->index(['assigned_to', 'status']);
            $table->index('ticket_number');
        });

        Schema::create('ticket_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('support_tickets')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->text('message');
            $table->json('attachments')->nullable();
            $table->boolean('is_internal')->default(false); // staff notes
            $table->timestamps();
            
            $table->index('ticket_id');
        });

        // Product Alerts
        Schema::create('product_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['price_drop', 'back_in_stock', 'low_stock']);
            $table->decimal('target_price', 10, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('notified')->default(false);
            $table->timestamp('notified_at')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'is_active']);
            $table->index(['product_id', 'type', 'is_active']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('product_alerts');
        Schema::dropIfExists('ticket_messages');
        Schema::dropIfExists('support_tickets');
        Schema::dropIfExists('auction_bids');
        Schema::dropIfExists('auctions');
        Schema::dropIfExists('offers');
    }
}
