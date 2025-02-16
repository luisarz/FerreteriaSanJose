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
        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();
            $table->foreignId('inventory_id')->constrained('inventories')->cascadeOnDelete();
            $table->decimal('quantity',10,2)->default(0); //cantidad vendida
            $table->decimal('price',10,2)->default(0); //precio de venta con iva
            $table->decimal('discount',10,2)->default(0); //descuento en dinero
            $table->decimal('total',10,2)->default(0); //total de la venta
            $table->boolean('is_except')->default(false); //si el producto NO tiene iva
            $table->decimal('exemptSale',10,2)->default(0); //total de la venta exenta
            $table->json('tributes')->nullable(); //impuestos aplicados
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sale_items');
    }
};
