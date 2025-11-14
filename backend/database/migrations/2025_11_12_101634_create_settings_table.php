<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();
            $table->string('group', 50)->default('general'); // general, email, payment, seo
            $table->text('value')->nullable();
            $table->string('type', 20)->default('text'); // text, number, boolean, json, password
            $table->boolean('is_public')->default(false); // Can be accessed without auth
            $table->text('description')->nullable();
            $table->timestamps();
            
            // Reduced length for composite index to avoid "key too long" error with utf8mb4
            $table->index(['group', 'key']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('settings');
    }
}
