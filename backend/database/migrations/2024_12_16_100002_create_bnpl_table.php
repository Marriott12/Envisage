<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBnplTable extends Migration
{
    public function up()
    {
        // Buy Now Pay Later plans
        Schema::create('bnpl_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // 4 payments, 6 payments, etc.
            $table->integer('installments'); // 4, 6, 12
            $table->integer('interval_days'); // 14, 30
            $table->decimal('interest_rate', 5, 2)->default(0); // 0% for promotional
            $table->decimal('minimum_amount', 10, 2)->default(50.00);
            $table->decimal('maximum_amount', 10, 2)->nullable();
            $table->boolean('active')->default(true);
            $table->text('terms')->nullable();
            $table->timestamps();
        });

        // User BNPL orders
        Schema::create('bnpl_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('bnpl_plan_id')->constrained()->onDelete('restrict');
            $table->decimal('total_amount', 10, 2);
            $table->decimal('down_payment', 10, 2)->default(0);
            $table->decimal('remaining_amount', 10, 2);
            $table->integer('installments_count');
            $table->integer('installments_paid')->default(0);
            $table->string('status'); // active, completed, defaulted, cancelled
            $table->date('first_payment_date');
            $table->date('next_payment_date')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'status']);
            $table->index('next_payment_date');
        });

        // Individual installment payments
        Schema::create('bnpl_installments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bnpl_order_id')->constrained()->onDelete('cascade');
            $table->integer('installment_number');
            $table->decimal('amount', 10, 2);
            $table->date('due_date');
            $table->date('paid_date')->nullable();
            $table->string('status'); // pending, paid, overdue, failed
            $table->string('payment_method')->nullable();
            $table->string('transaction_id')->nullable();
            $table->decimal('late_fee', 10, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['bnpl_order_id', 'status']);
            $table->index('due_date');
        });
    }

    public function down()
    {
        Schema::dropIfExists('bnpl_installments');
        Schema::dropIfExists('bnpl_orders');
        Schema::dropIfExists('bnpl_plans');
    }
}
