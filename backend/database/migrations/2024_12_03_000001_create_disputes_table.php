<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDisputesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('disputes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->enum('type', ['return', 'refund', 'complaint', 'quality_issue', 'not_received'])->default('refund');
            $table->enum('status', ['pending', 'approved', 'rejected', 'resolved', 'escalated'])->default('pending');
            $table->decimal('amount', 10, 2)->nullable();
            $table->string('reason');
            $table->text('description')->nullable();
            $table->json('evidence')->nullable(); // Store image URLs as JSON array
            $table->text('admin_response')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            // Indexes for performance
            $table->index('order_id');
            $table->index('user_id');
            $table->index('status');
            $table->index('type');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('disputes');
    }
}
