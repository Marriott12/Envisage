<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymentMethodsTable extends Migration
{
    public function up()
    {
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['card', 'paypal', 'bank_account', 'wallet']);
            $table->string('provider')->default('stripe'); // stripe, paypal, etc
            $table->string('provider_payment_method_id'); // Stripe payment method ID
            $table->string('last_four')->nullable();
            $table->string('brand')->nullable(); // visa, mastercard, etc
            $table->integer('exp_month')->nullable();
            $table->integer('exp_year')->nullable();
            $table->string('holder_name')->nullable();
            $table->string('email')->nullable(); // for PayPal
            $table->boolean('is_default')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'is_default']);
            $table->index('provider_payment_method_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('payment_methods');
    }
}
