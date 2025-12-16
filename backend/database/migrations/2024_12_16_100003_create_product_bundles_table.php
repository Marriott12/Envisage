<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductBundlesTable extends Migration
{
    public function up()
    {
        // Product bundles/kits
        Schema::create('product_bundles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_id')->constrained('users')->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('slug')->unique();
            $table->decimal('regular_price', 10, 2);
            $table->decimal('bundle_price', 10, 2);
            $table->decimal('discount_amount', 10, 2);
            $table->decimal('discount_percentage', 5, 2);
            $table->string('image')->nullable();
            $table->boolean('active')->default(true);
            $table->integer('stock_quantity')->default(0);
            $table->integer('sold_count')->default(0);
            $table->date('starts_at')->nullable();
            $table->date('expires_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['active', 'expires_at']);
            $table->index('seller_id');
        });

        // Bundle items (products in the bundle)
        Schema::create('bundle_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bundle_id')->constrained('product_bundles')->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->integer('quantity')->default(1);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            
            $table->unique(['bundle_id', 'product_id']);
        });

        // Frequently bought together suggestions
        Schema::create('product_associations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('associated_product_id')->constrained('products')->onDelete('cascade');
            $table->integer('purchase_count')->default(0);
            $table->decimal('association_score', 5, 2)->default(0); // 0-100
            $table->string('type')->default('bought_together'); // bought_together, viewed_together
            $table->timestamps();
            
            $table->unique(['product_id', 'associated_product_id', 'type']);
            $table->index(['product_id', 'association_score']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('product_associations');
        Schema::dropIfExists('bundle_items');
        Schema::dropIfExists('product_bundles');
    }
}
