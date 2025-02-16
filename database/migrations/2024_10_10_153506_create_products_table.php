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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('aplications')->nullable();
            $table->string('sku')->nullable();
            $table->string('bar_code')->nullable();
            $table->boolean('is_service')->default(false);
            $table->foreignId('category_id')->constrained('categories')->onDelete('cascade');
            $table->foreignId('marca_id')->constrained('marcas')->onDelete('cascade');
            $table->foreignId('unit_measurement_id')->constrained('unit_measurements')->onDelete('cascade');
            $table->boolean('is_taxed')->default(true);
            $table->json('images')->nullable();
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
