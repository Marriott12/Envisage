<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVideoReviewsTable extends Migration
{
    public function up()
    {
        // Video reviews
        Schema::create('video_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_review_id')->constrained()->onDelete('cascade');
            $table->string('video_path');
            $table->string('thumbnail_path')->nullable();
            $table->integer('duration_seconds')->nullable();
            $table->integer('file_size_kb')->nullable();
            $table->string('encoding_status')->default('pending'); // pending, processing, completed, failed
            $table->integer('views_count')->default(0);
            $table->timestamps();
            
            $table->index('product_review_id');
        });

        // Add video support flag to reviews
        if (!Schema::hasColumn('product_reviews', 'has_video')) {
            Schema::table('product_reviews', function (Blueprint $table) {
                $table->boolean('has_video')->default(false)->after('status');
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('product_reviews', 'has_video')) {
            Schema::table('product_reviews', function (Blueprint $table) {
                $table->dropColumn('has_video');
            });
        }
        Schema::dropIfExists('video_reviews');
    }
}
