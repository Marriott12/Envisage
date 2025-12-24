<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tax_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('country', 2); // ISO 2-letter code
            $table->string('state')->nullable();
            $table->string('city')->nullable();
            $table->string('zip_code')->nullable();
            
            $table->enum('type', ['sales_tax', 'vat', 'gst', 'custom'])->default('sales_tax');
            $table->decimal('rate', 5, 4); // e.g., 0.0850 for 8.5%
            
            $table->boolean('is_compound')->default(false); // Tax on tax
            $table->integer('priority')->default(0); // For multiple tax rules
            
            $table->boolean('is_active')->default(true);
            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable();
            
            // Product category specific
            $table->json('applicable_categories')->nullable();
            $table->json('excluded_categories')->nullable();
            
            $table->text('description')->nullable();
            
            $table->timestamps();
            
            $table->index(['country', 'state', 'is_active']);
            $table->index('type');
        });

        Schema::create('tax_exemptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('category_id')->nullable()->constrained()->onDelete('cascade');
            
            $table->enum('type', ['user', 'product', 'category'])->default('user');
            $table->string('exemption_certificate')->nullable();
            $table->string('tax_id')->nullable(); // Tax exemption ID
            $table->string('reason');
            
            $table->boolean('is_active')->default(true);
            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable();
            
            $table->timestamps();
            
            $table->index('user_id');
            $table->index('product_id');
            $table->index('type');
        });

        Schema::create('tax_reports', function (Blueprint $table) {
            $table->id();
            $table->string('report_number')->unique();
            $table->enum('type', ['monthly', 'quarterly', 'annual', 'custom']);
            $table->date('period_start');
            $table->date('period_end');
            
            $table->string('country', 2);
            $table->string('state')->nullable();
            
            $table->decimal('total_sales', 12, 2)->default(0);
            $table->decimal('taxable_sales', 12, 2)->default(0);
            $table->decimal('exempt_sales', 12, 2)->default(0);
            $table->decimal('tax_collected', 12, 2)->default(0);
            
            $table->json('breakdown')->nullable(); // Detailed breakdown by tax type
            
            $table->enum('status', ['draft', 'finalized', 'filed', 'paid'])->default('draft');
            $table->timestamp('filed_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            
            $table->string('pdf_path')->nullable();
            
            $table->timestamps();
            
            $table->index('report_number');
            $table->index(['country', 'state']);
            $table->index('status');
        });

        Schema::create('tax_calculations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            
            $table->string('country', 2);
            $table->string('state')->nullable();
            $table->string('city')->nullable();
            $table->string('zip_code')->nullable();
            
            $table->decimal('subtotal', 10, 2);
            $table->decimal('shipping', 10, 2)->default(0);
            $table->decimal('total_tax', 10, 2);
            
            $table->json('tax_breakdown'); // Array of applied taxes
            $table->json('exemptions_applied')->nullable();
            
            $table->timestamps();
            
            $table->index('order_id');
            $table->index(['country', 'state']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tax_calculations');
        Schema::dropIfExists('tax_reports');
        Schema::dropIfExists('tax_exemptions');
        Schema::dropIfExists('tax_rules');
    }
};
