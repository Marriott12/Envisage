<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCheckoutEnhancementsTable extends Migration
{
    public function up()
    {
        // Promo codes table
        Schema::create('promo_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->enum('type', ['percentage', 'fixed'])->default('percentage');
            $table->decimal('value', 10, 2); // percentage or fixed amount
            $table->decimal('minimum_order_amount', 10, 2)->default(0);
            $table->decimal('maximum_discount', 10, 2)->nullable();
            $table->integer('usage_limit')->nullable(); // null = unlimited
            $table->integer('usage_count')->default(0);
            $table->integer('per_user_limit')->default(1);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Promo code usage tracking
        Schema::create('promo_code_usage', function (Blueprint $table) {
            $table->id();
            $table->foreignId('promo_code_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('order_id')->nullable()->constrained()->onDelete('set null');
            $table->decimal('discount_amount', 10, 2);
            $table->timestamp('used_at')->useCurrent();
        });

        // Guest checkout sessions
        Schema::create('guest_checkout_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('session_token')->unique();
            $table->string('email');
            $table->string('name');
            $table->string('phone')->nullable();
            $table->json('shipping_address');
            $table->json('cart_data');
            $table->timestamp('expires_at');
            $table->timestamps();
        });

        // Saved payment methods (tokenized)
        Schema::create('saved_payment_methods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('payment_provider'); // stripe, paypal, etc.
            $table->string('payment_method_id'); // Stripe payment method ID
            $table->string('type'); // card, bank_account
            $table->string('last4')->nullable();
            $table->string('brand')->nullable(); // visa, mastercard
            $table->integer('exp_month')->nullable();
            $table->integer('exp_year')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('saved_payment_methods');
        Schema::dropIfExists('guest_checkout_sessions');
        Schema::dropIfExists('promo_code_usage');
        Schema::dropIfExists('promo_codes');
    }
}
