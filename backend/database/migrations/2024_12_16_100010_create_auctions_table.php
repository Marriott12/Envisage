<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAuctionsTable extends Migration
{
    public function up()
    {
        // Auction listings
        Schema::create('auctions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('seller_id')->constrained('users')->onDelete('cascade');
            $table->decimal('starting_bid', 10, 2);
            $table->decimal('reserve_price', 10, 2)->nullable();
            $table->decimal('current_bid', 10, 2)->default(0);
            $table->decimal('buy_now_price', 10, 2)->nullable();
            $table->decimal('bid_increment', 10, 2)->default(1.00);
            $table->foreignId('highest_bidder_id')->nullable()->constrained('users')->onDelete('set null');
            $table->integer('bid_count')->default(0);
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->string('status')->default('scheduled'); // scheduled, active, ended, sold, cancelled
            $table->boolean('featured')->default(false);
            $table->integer('views_count')->default(0);
            $table->integer('watchers_count')->default(0);
            $table->timestamps();
            
            $table->index(['status', 'ends_at']);
            $table->index('seller_id');
        });

        // Bids placed
        Schema::create('auction_bids', function (Blueprint $table) {
            $table->id();
            $table->foreignId('auction_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->decimal('bid_amount', 10, 2);
            $table->boolean('is_auto_bid')->default(false);
            $table->decimal('max_auto_bid', 10, 2)->nullable();
            $table->boolean('is_winning')->default(false);
            $table->boolean('outbid')->default(false);
            $table->timestamps();
            
            $table->index(['auction_id', 'created_at']);
            $table->index(['user_id', 'is_winning']);
        });

        // Auto-bidding configurations
        Schema::create('auto_bids', function (Blueprint $table) {
            $table->id();
            $table->foreignId('auction_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->decimal('max_bid', 10, 2);
            $table->boolean('active')->default(true);
            $table->timestamps();
            
            $table->unique(['auction_id', 'user_id']);
        });

        // Auction watchers
        Schema::create('auction_watchers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('auction_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->boolean('notify_outbid')->default(true);
            $table->boolean('notify_ending_soon')->default(true);
            $table->timestamps();
            
            $table->unique(['auction_id', 'user_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('auction_watchers');
        Schema::dropIfExists('auto_bids');
        Schema::dropIfExists('auction_bids');
        Schema::dropIfExists('auctions');
    }
}
