<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGiftCardsTable extends Migration
{
    public function up()
    {
        // Gift card products/templates
        Schema::create('gift_card_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->json('denominations'); // [25, 50, 100, 250, 500]
            $table->boolean('allow_custom_amount')->default(true);
            $table->decimal('min_amount', 10, 2)->default(10.00);
            $table->decimal('max_amount', 10, 2)->default(1000.00);
            $table->integer('validity_days')->default(365);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        // Issued gift cards
        Schema::create('gift_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->nullable()->constrained('gift_card_templates')->onDelete('set null');
            $table->string('code')->unique();
            $table->decimal('original_amount', 10, 2);
            $table->decimal('current_balance', 10, 2);
            $table->foreignId('purchaser_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('recipient_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('recipient_email')->nullable();
            $table->string('recipient_name')->nullable();
            $table->text('message')->nullable();
            $table->string('status')->default('active'); // active, redeemed, expired, cancelled
            $table->timestamp('purchased_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
            
            $table->index('code');
            $table->index(['recipient_email', 'status']);
        });

        // Gift card transactions
        Schema::create('gift_card_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gift_card_id')->constrained()->onDelete('cascade');
            $table->foreignId('order_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->decimal('amount', 10, 2);
            $table->decimal('balance_before', 10, 2);
            $table->decimal('balance_after', 10, 2);
            $table->string('type'); // purchase, redemption, refund, adjustment
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index('gift_card_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('gift_card_transactions');
        Schema::dropIfExists('gift_cards');
        Schema::dropIfExists('gift_card_templates');
    }
}
