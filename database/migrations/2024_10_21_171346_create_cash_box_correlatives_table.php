<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cash_box_correlatives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cash_box_id')->references('id')->on('cash_boxes');
            $table->foreignId('document_type_id')->references('id')->on('document_types');
            $table->string('serie');
            $table->integer('start_number')->default(1)->nullable(false);
            $table->integer('end_number')->default(1)->nullable(false);
            $table->integer('current_number')->default(1)->nullable(false);
            $table->boolean('is_active')->nullable(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_box_correlatives');
    }
};
