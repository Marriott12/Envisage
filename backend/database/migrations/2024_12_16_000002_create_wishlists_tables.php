<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWishlistsTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Wishlists table
        Schema::create('wishlists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name')->default('My Wishlist');
            $table->text('description')->nullable();
            $table->enum('privacy', ['private', 'public', 'shared'])->default('private');
            $table->string('share_token')->unique()->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->index(['user_id', 'is_default']);
            $table->index('share_token');
        });

        // Wishlist items table
        Schema::create('wishlist_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wishlist_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->text('note')->nullable();
            $table->integer('priority')->default(0); // 0=low, 1=medium, 2=high
            $table->decimal('price_when_added', 10, 2);
            $table->timestamps();

            $table->unique(['wishlist_id', 'product_id']);
            $table->index('wishlist_id');
            $table->index('product_id');
        });

        // Wishlist sharing/collaborators
        Schema::create('wishlist_shares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wishlist_id')->constrained()->onDelete('cascade');
            $table->string('email')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->enum('permission', ['view', 'edit'])->default('view');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index('wishlist_id');
            $table->index(['email', 'wishlist_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('wishlist_shares');
        Schema::dropIfExists('wishlist_items');
        Schema::dropIfExists('wishlists');
    }
}
