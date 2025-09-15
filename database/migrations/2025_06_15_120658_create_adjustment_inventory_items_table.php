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
        Schema::create('adjustment_inventory_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('adjustment_id')->constrained('adjustment_inventories');
            $table->foreignId('inventory_id')->constrained('inventories');
            $table->decimal('cantidad');
            $table->decimal('precio_unitario');
            $table->decimal('precio_total');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('adjustment_inventory_items');
    }
};
