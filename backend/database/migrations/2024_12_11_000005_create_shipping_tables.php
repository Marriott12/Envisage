<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShippingTables extends Migration
{
    public function up()
    {
        Schema::create('shipping_rates', function (Blueprint $table) {
            $table->id();
            $table->string('carrier'); // UPS, FedEx, USPS, DHL
            $table->string('service_code'); // GROUND, 2DAY, OVERNIGHT, etc
            $table->string('service_name');
            $table->decimal('base_rate', 8, 2);
            $table->decimal('per_kg_rate', 8, 2)->default(0);
            $table->integer('min_weight_kg')->default(0);
            $table->integer('max_weight_kg')->nullable();
            $table->string('from_country')->default('US');
            $table->string('to_country')->nullable();
            $table->string('from_postal_code')->nullable();
            $table->string('to_postal_code')->nullable();
            $table->integer('delivery_days_min')->nullable();
            $table->integer('delivery_days_max')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index(['carrier', 'is_active']);
            $table->index('from_country');
            $table->index('to_country');
        });

        Schema::create('shipping_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('label')->nullable(); // Home, Work, etc
            $table->string('full_name');
            $table->string('phone');
            $table->string('address_line1');
            $table->string('address_line2')->nullable();
            $table->string('city');
            $table->string('state');
            $table->string('postal_code');
            $table->string('country')->default('US');
            $table->boolean('is_validated')->default(false);
            $table->json('validation_response')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
            
            $table->index(['user_id', 'is_default']);
            $table->index('postal_code');
        });
    }

    public function down()
    {
        Schema::dropIfExists('shipping_addresses');
        Schema::dropIfExists('shipping_rates');
    }
}
