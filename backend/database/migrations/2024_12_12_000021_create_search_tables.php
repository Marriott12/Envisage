<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSearchTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Search Logs - Track all search queries for analytics
        Schema::create('search_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('query', 191); // Limited to 191 for indexing
            $table->integer('results_count');
            $table->string('filters')->nullable(); // JSON encoded
            $table->string('sort_by')->nullable();
            $table->integer('clicked_product_id')->nullable();
            $table->integer('click_position')->nullable();
            $table->string('session_id', 100);
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->decimal('response_time_ms', 8, 2)->nullable();
            $table->timestamps();
            
            $table->index('user_id');
            $table->index('query'); // Now safe with 191 char limit
            $table->index('session_id');
            $table->index('created_at');
        });

        // Search Synonyms - Handle different terms for same concepts
        Schema::create('search_synonyms', function (Blueprint $table) {
            $table->id();
            $table->string('term', 100);
            $table->text('synonyms'); // JSON array
            $table->enum('type', ['synonym', 'misspelling', 'abbreviation'])->default('synonym');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index('term');
            $table->index(['type', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('search_synonyms');
        Schema::dropIfExists('search_logs');
    }
}
