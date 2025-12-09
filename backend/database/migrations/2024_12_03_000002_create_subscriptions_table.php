<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSubscriptionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('subscription_plan_id')->constrained('subscription_plans')->onDelete('restrict');
            $table->enum('status', ['active', 'cancelled', 'expired', 'past_due', 'trial'])->default('active');
            $table->enum('billing_cycle', ['monthly', 'yearly'])->default('monthly');
            $table->date('start_date');
            $table->date('end_date');
            $table->date('next_billing_date')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('user_id');
            $table->index('subscription_plan_id');
            $table->index('status');
            $table->index('end_date');
            $table->index('next_billing_date');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('subscriptions');
    }
}
