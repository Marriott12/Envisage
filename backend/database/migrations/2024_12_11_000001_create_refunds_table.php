<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRefundsTable extends Migration
{
    public function up()
    {
        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('order_item_id')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'processing', 'completed'])->default('pending');
            $table->decimal('amount', 10, 2);
            $table->string('reason');
            $table->text('description')->nullable();
            $table->text('admin_notes')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('refund_reference')->nullable();
            $table->json('attachments')->nullable();
            $table->timestamp('requested_at')->useCurrent();
            $table->timestamp('processed_at')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            $table->index(['order_id', 'status']);
            $table->index(['user_id', 'status']);
            $table->index('status');
            $table->index('requested_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('refunds');
    }
}
