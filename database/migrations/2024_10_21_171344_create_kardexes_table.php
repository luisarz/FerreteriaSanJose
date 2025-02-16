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
        Schema::create('kardex', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->date('date');
            $table->string('operation_type')->nullable()    ;
            $table->string('operation_id')->nullable();
            $table->integer('operation_detail_id')->nullable();
            $table->string('document_type')->nullable();
            $table->string('document_number')->nullable();
            $table->string('entity')->nullable();
            $table->string('nationality')->nullable();
            $table->foreignId('inventory_id')->constrained('inventories')->cascadeOnDelete();
            $table->decimal('previous_stock', 10, 2);
            $table->decimal('stock_in', 10, 2);
            $table->decimal('stock_out', 10, 2);
            $table->decimal('stock_actual', 10, 2);
            $table->decimal('money_in', 10, 2);
            $table->decimal('money_out', 10, 2);
            $table->decimal('money_actual', 10, 2);
            $table->decimal('sale_price', 10, 2)->default(0);//Cuanto es venta
            $table->decimal('purchase_price', 10, 2)->default(0); //Cuando es compra
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kardexes');
    }
};
