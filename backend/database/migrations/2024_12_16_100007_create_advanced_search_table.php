<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAdvancedSearchTable extends Migration
{
    public function up()
    {
        // Search filters/facets configuration
        Schema::create('search_filters', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type'); // range, checkbox, radio, color, size
            $table->string('field'); // price, category_id, brand, rating, etc.
            $table->json('options')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        // Search history per user
        Schema::create('search_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('session_id')->nullable();
            $table->string('query');
            $table->integer('results_count')->default(0);
            $table->boolean('clicked_result')->default(false);
            $table->foreignId('clicked_product_id')->nullable()->constrained('products')->onDelete('set null');
            $table->timestamps();
            
            $table->index(['user_id', 'created_at']);
            $table->index('query');
        });

        // Popular searches
        Schema::create('popular_searches', function (Blueprint $table) {
            $table->id();
            $table->string('query')->unique();
            $table->integer('search_count')->default(1);
            $table->integer('click_count')->default(0);
            $table->decimal('click_through_rate', 5, 2)->default(0);
            $table->date('last_searched');
            $table->timestamps();
            
            $table->index('search_count');
        });

        // Search suggestions/autocomplete
        Schema::create('search_suggestions', function (Blueprint $table) {
            $table->id();
            $table->string('term');
            $table->string('suggestion');
            $table->integer('relevance_score')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();
            
            $table->index('term');
        });
    }

    public function down()
    {
        Schema::dropIfExists('search_suggestions');
        Schema::dropIfExists('popular_searches');
        Schema::dropIfExists('search_history');
        Schema::dropIfExists('search_filters');
    }
}
