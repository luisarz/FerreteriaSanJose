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
        // Índices para tabla sales - consultas frecuentes
        Schema::table('sales', function (Blueprint $table) {
            $table->index('operation_date', 'idx_sales_operation_date');
            $table->index('sale_status', 'idx_sales_status');
            $table->index('operation_type', 'idx_sales_operation_type');
            $table->index(['is_dte', 'is_hacienda_send'], 'idx_sales_dte_status');
            $table->index('generationCode', 'idx_sales_generation_code');
            $table->index('is_invoiced', 'idx_sales_is_invoiced');
            $table->index(['wherehouse_id', 'operation_date'], 'idx_sales_branch_date');
        });

        // Índices para tabla kardex - reportes por fecha
        Schema::table('kardex', function (Blueprint $table) {
            $table->index('date', 'idx_kardex_date');
            $table->index('operation_type', 'idx_kardex_operation_type');
            $table->index(['inventory_id', 'date'], 'idx_kardex_inventory_date');
        });

        // Índices para tabla customers - búsquedas frecuentes
        Schema::table('customers', function (Blueprint $table) {
            $table->index('name', 'idx_customers_name');
            $table->index('last_name', 'idx_customers_last_name');
            $table->index('dui', 'idx_customers_dui');
            $table->index('nit', 'idx_customers_nit');
            $table->index('nrc', 'idx_customers_nrc');
        });

        // Índices para tabla products - búsquedas por nombre y SKU
        Schema::table('products', function (Blueprint $table) {
            $table->index('name', 'idx_products_name');
            $table->index('sku', 'idx_products_sku');
            $table->index('bar_code', 'idx_products_bar_code');
        });

        // Índices para tabla sale_items
        Schema::table('sale_items', function (Blueprint $table) {
            $table->index('sale_id', 'idx_sale_items_sale_id');
            $table->index('inventory_id', 'idx_sale_items_inventory_id');
        });

        // Índices para cash_box_correlatives
        Schema::table('cash_box_correlatives', function (Blueprint $table) {
            $table->index(['cash_box_id', 'document_type_id'], 'idx_correlatives_cashbox_doctype');
        });

        // Índices para history_dtes
        Schema::table('history_dtes', function (Blueprint $table) {
            $table->index('codigoGeneracion', 'idx_history_dtes_codigo');
            $table->index('estado', 'idx_history_dtes_estado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex('idx_sales_operation_date');
            $table->dropIndex('idx_sales_status');
            $table->dropIndex('idx_sales_operation_type');
            $table->dropIndex('idx_sales_dte_status');
            $table->dropIndex('idx_sales_generation_code');
            $table->dropIndex('idx_sales_is_invoiced');
            $table->dropIndex('idx_sales_branch_date');
        });

        Schema::table('kardex', function (Blueprint $table) {
            $table->dropIndex('idx_kardex_date');
            $table->dropIndex('idx_kardex_operation_type');
            $table->dropIndex('idx_kardex_inventory_date');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex('idx_customers_name');
            $table->dropIndex('idx_customers_last_name');
            $table->dropIndex('idx_customers_dui');
            $table->dropIndex('idx_customers_nit');
            $table->dropIndex('idx_customers_nrc');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('idx_products_name');
            $table->dropIndex('idx_products_sku');
            $table->dropIndex('idx_products_bar_code');
        });

        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropIndex('idx_sale_items_sale_id');
            $table->dropIndex('idx_sale_items_inventory_id');
        });

        Schema::table('cash_box_correlatives', function (Blueprint $table) {
            $table->dropIndex('idx_correlatives_cashbox_doctype');
        });

        Schema::table('history_dtes', function (Blueprint $table) {
            $table->dropIndex('idx_history_dtes_codigo');
            $table->dropIndex('idx_history_dtes_estado');
        });
    }
};
