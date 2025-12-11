<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLoyaltyTiersTable extends Migration
{
    public function up()
    {
        Schema::create('loyalty_tiers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->integer('min_points')->default(0);
            $table->integer('max_points')->nullable();
            $table->decimal('multiplier', 3, 1)->default(1.0);
            $table->integer('discount_percentage')->default(0);
            $table->string('color')->default('#6366f1');
            $table->string('icon')->default('⭐');
            $table->json('benefits')->nullable();
            $table->json('bonuses')->nullable(); // welcome, birthday, referral
            $table->boolean('is_active')->default(true);
            $table->integer('order')->default(0);
            $table->timestamps();
            
            $table->index('min_points');
            $table->index('is_active');
            $table->index('order');
        });

        // Add tier_id to users table
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'loyalty_tier_id')) {
                $table->foreignId('loyalty_tier_id')->nullable()->constrained('loyalty_tiers')->onDelete('set null');
            }
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'loyalty_tier_id')) {
                $table->dropForeign(['loyalty_tier_id']);
                $table->dropColumn('loyalty_tier_id');
            }
        });
        
        Schema::dropIfExists('loyalty_tiers');
    }
}
