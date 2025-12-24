<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('currencies');
        Schema::dropIfExists('exchange_rates');
        Schema::dropIfExists('currency_conversions');
        
        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->string('code', 3)->unique(); // USD, EUR, GBP
            $table->string('name'); // US Dollar
            $table->string('symbol'); // $
            $table->decimal('rate', 12, 6)->default(1.000000); // Exchange rate to base currency
            $table->boolean('is_active')->default(true);
            $table->boolean('is_base')->default(false); // Base currency for conversions
            $table->integer('decimal_places')->default(2);
            $table->string('format')->default('{symbol}{amount}'); // Currency display format
            $table->timestamps();
            
            $table->index('code');
            $table->index('is_active');
        });

        Schema::create('exchange_rates', function (Blueprint $table) {
            $table->id();
            $table->string('from_currency', 3);
            $table->string('to_currency', 3);
            $table->decimal('rate', 12, 6);
            $table->string('source')->default('manual'); // manual, api, auto
            $table->timestamps();
            
            $table->index(['from_currency', 'to_currency']);
            $table->index('updated_at');
        });

        Schema::create('currency_conversions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('from_currency', 3);
            $table->string('to_currency', 3);
            $table->decimal('from_amount', 12, 2);
            $table->decimal('to_amount', 12, 2);
            $table->decimal('rate', 12, 6);
            $table->string('context')->nullable(); // 'order', 'product_view', etc.
            $table->timestamps();
            
            $table->index(['from_currency', 'to_currency']);
            $table->index('user_id');
        });

        // Add currency columns to users table
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'preferred_currency')) {
                $table->string('preferred_currency', 3)->default('USD')->after('email');
            }
        });

        // Add currency columns to orders table
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'currency')) {
                $table->string('currency', 3)->default('USD')->after('total');
            }
            if (!Schema::hasColumn('orders', 'display_currency')) {
                $table->string('display_currency', 3)->nullable()->after('currency');
            }
            if (!Schema::hasColumn('orders', 'display_total')) {
                $table->decimal('display_total', 10, 2)->nullable()->after('display_currency');
            }
        });

        // Add currency to products table
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'currency')) {
                $table->string('currency', 3)->default('USD')->after('price');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('currency');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['currency', 'display_currency', 'display_total']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('preferred_currency');
        });

        Schema::dropIfExists('currency_conversions');
        Schema::dropIfExists('exchange_rates');
        Schema::dropIfExists('currencies');
    }
};
