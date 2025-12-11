<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReportsTable extends Migration
{
    public function up()
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['sales', 'users', 'products', 'revenue', 'customers', 'inventory', 'custom']);
            $table->date('start_date');
            $table->date('end_date');
            $table->string('format')->default('pdf');
            $table->string('file_path')->nullable();
            $table->integer('file_size')->nullable(); // in bytes
            $table->foreignId('generated_by')->constrained('users')->onDelete('cascade');
            $table->json('filters')->nullable();
            $table->json('data')->nullable();
            $table->integer('download_count')->default(0);
            $table->timestamp('last_downloaded_at')->nullable();
            $table->timestamps();
            
            $table->index(['type', 'created_at']);
            $table->index('generated_by');
            $table->index('created_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('reports');
    }
}
