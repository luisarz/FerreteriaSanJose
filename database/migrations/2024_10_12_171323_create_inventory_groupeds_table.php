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
        Schema::create('inventory_groupeds', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('inventory_grouped_id');
            $table->foreign('inventory_id')->references('id')->on('inventories');
            $table->unsignedBigInteger('inventory_child_id');
            $table->foreign('inventory_child_id')->references('id')->on('inventories');
            $table->integer('quantity');
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
        Schema::dropIfExists('inventory_groupeds');
    }
};
