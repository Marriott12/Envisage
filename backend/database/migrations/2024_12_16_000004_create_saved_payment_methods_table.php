<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSavedPaymentMethodsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Saved payment methods
        Schema::create('saved_payment_methods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['card', 'paypal', 'bank_account']);
            $table->string('provider')->nullable(); // stripe, paypal, etc.
            $table->string('provider_payment_method_id')->nullable();
            $table->string('last_four')->nullable();
            $table->string('card_brand')->nullable(); // visa, mastercard, etc.
            $table->string('card_holder_name')->nullable();
            $table->integer('expiry_month')->nullable();
            $table->integer('expiry_year')->nullable();
            $table->string('billing_address_id')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->index(['user_id', 'is_default']);
        });

        // Saved addresses
        Schema::create('saved_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['shipping', 'billing', 'both'])->default('both');
            $table->string('label')->nullable(); // "Home", "Office", etc.
            $table->string('full_name');
            $table->string('phone');
            $table->string('address_line_1');
            $table->string('address_line_2')->nullable();
            $table->string('city');
            $table->string('state');
            $table->string('postal_code');
            $table->string('country');
            $table->boolean('is_default_shipping')->default(false);
            $table->boolean('is_default_billing')->default(false);
            $table->timestamps();

            $table->index(['user_id', 'type']);
            $table->index(['user_id', 'is_default_shipping']);
            $table->index(['user_id', 'is_default_billing']);
        });

        // Express checkout preferences
        Schema::create('express_checkout_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->onDelete('cascade');
            $table->boolean('enabled')->default(false);
            $table->foreignId('default_payment_method_id')->nullable()->constrained('saved_payment_methods')->onDelete('set null');
            $table->foreignId('default_shipping_address_id')->nullable()->constrained('saved_addresses')->onDelete('set null');
            $table->foreignId('default_billing_address_id')->nullable()->constrained('saved_addresses')->onDelete('set null');
            $table->boolean('skip_review_step')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('express_checkout_preferences');
        Schema::dropIfExists('saved_addresses');
        Schema::dropIfExists('saved_payment_methods');
    }
}
