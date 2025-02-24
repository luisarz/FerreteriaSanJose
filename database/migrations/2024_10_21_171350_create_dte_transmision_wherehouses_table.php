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
        Schema::create('dte_transmision_wherehouses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wherehouse')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('billing_model')->constrained('billing_models')->cascadeOnDelete();
            $table->foreignId('transmision_type')->constrained('transmision_types')->cascadeOnDelete();
            $table->integer('printer_type')->default(1);
            $table->timestamps();
            $table->unique('wherehouse');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dte_transmision_wherehouses');
    }
};
