<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class EnhanceReviewsTable extends Migration
{
    public function up()
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->foreignId('order_id')->nullable()->after('user_id')->constrained()->onDelete('set null');
            $table->string('title')->nullable()->after('rating');
            $table->text('comment')->nullable()->after('review');
            $table->json('images')->nullable()->after('comment');
            $table->integer('helpful_count')->default(0)->after('verified_purchase');
            $table->integer('not_helpful_count')->default(0)->after('helpful_count');
        });

        Schema::create('review_helpfulness', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('review_id')->constrained()->onDelete('cascade');
            $table->boolean('is_helpful');
            $table->timestamps();

            $table->unique(['user_id', 'review_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('review_helpfulness');
        
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropForeign(['order_id']);
            $table->dropColumn(['order_id', 'title', 'comment', 'images', 'helpful_count', 'not_helpful_count']);
        });
    }
}
