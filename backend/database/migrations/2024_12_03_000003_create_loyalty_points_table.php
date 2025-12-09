<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLoyaltyPointsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('loyalty_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->onDelete('cascade');
            $table->integer('balance')->default(0);
            $table->integer('lifetime_earned')->default(0);
            $table->integer('lifetime_redeemed')->default(0);
            $table->enum('tier', ['bronze', 'silver', 'gold', 'platinum', 'diamond'])->default('bronze');
            $table->string('referral_code', 20)->unique()->nullable();
            $table->timestamps();

            // Indexes
            $table->index('user_id');
            $table->index('tier');
            $table->index('balance');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('loyalty_points');
    }
}
