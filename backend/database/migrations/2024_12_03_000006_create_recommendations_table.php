<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRecommendationsTable extends Migration
{
    public function up()
    {
        // Product views tracking
        Schema::create('product_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('session_id')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
            
            $table->index(['product_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });

        // Product similarity scores (pre-computed for performance)
        Schema::create('product_similarities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('similar_product_id')->constrained('products')->onDelete('cascade');
            $table->decimal('similarity_score', 5, 4); // 0.0000 to 1.0000
            $table->string('similarity_type'); // category, tags, collaborative_filtering, etc.
            $table->timestamps();
            
            $table->index(['product_id', 'similarity_score']);
            $table->unique(['product_id', 'similar_product_id', 'similarity_type']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('product_similarities');
        Schema::dropIfExists('product_views');
    }
}
