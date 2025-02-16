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
        Schema::create('inventories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->integer('stock')->default(0);
            $table->integer('stock_min')->default(0);
            $table->integer('stock_max')->default(0);
            $table->decimal('cost_without_taxes', 10, 2)->default(0);
            $table->decimal('cost_with_taxes', 10, 2)->default(0);
            $table->boolean('is_stock_alert')->default(false);
            $table->boolean('is_expiration_date')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unique(['product_id', 'branch_id']);
            $table->softDeletes();
            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventories');
    }
};
