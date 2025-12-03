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
        Schema::table('kardex', function (Blueprint $table) {
            // Índice compuesto para búsquedas por inventario y fecha (muy común)
            $table->index(['inventory_id', 'date'], 'idx_kardex_inventory_date');

            // Índice para búsquedas por número de documento
            $table->index('document_number', 'idx_kardex_document_number');

            // Índice para búsquedas por entidad (cliente/proveedor)
            $table->index('entity', 'idx_kardex_entity');

            // Índice compuesto para filtros por sucursal y fecha
            $table->index(['branch_id', 'date'], 'idx_kardex_branch_date');

            // Índice para filtros por tipo de operación
            $table->index('operation_type', 'idx_kardex_operation_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kardex', function (Blueprint $table) {
            $table->dropIndex('idx_kardex_inventory_date');
            $table->dropIndex('idx_kardex_document_number');
            $table->dropIndex('idx_kardex_entity');
            $table->dropIndex('idx_kardex_branch_date');
            $table->dropIndex('idx_kardex_operation_type');
        });
    }
};
