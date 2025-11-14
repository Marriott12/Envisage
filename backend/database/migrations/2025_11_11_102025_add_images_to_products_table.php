<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddImagesToProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('products', function (Blueprint $table) {
            // Drop old image_url column if exists
            if (Schema::hasColumn('products', 'image_url')) {
                $table->dropColumn('image_url');
            }
            
            // Add new image columns if they don't exist
            if (!Schema::hasColumn('products', 'primary_image')) {
                $table->string('primary_image')->nullable()->after('stock');
            }
            
            // Update existing images column to JSON if needed
            if (Schema::hasColumn('products', 'images')) {
                DB::statement('ALTER TABLE products MODIFY images JSON NULL');
            }
            
            if (!Schema::hasColumn('products', 'thumbnail')) {
                $table->string('thumbnail')->nullable()->after('images');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['primary_image', 'images', 'thumbnail']);
            $table->string('image_url')->nullable();
        });
    }
}
