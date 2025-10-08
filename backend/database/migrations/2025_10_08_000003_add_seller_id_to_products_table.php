<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('seller_id')->nullable()->after('id')->constrained('users')->onDelete('set null');
            $table->boolean('featured')->default(false)->after('stock');
            $table->integer('views')->default(0)->after('featured');
            $table->enum('status', ['active', 'inactive', 'sold'])->default('active')->after('views');
            
            // Add indexes for performance
            $table->index('seller_id');
            $table->index('category');
            $table->index('status');
            $table->index(['featured', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['seller_id']);
            $table->dropColumn(['seller_id', 'featured', 'views', 'status']);
        });
    }
};
