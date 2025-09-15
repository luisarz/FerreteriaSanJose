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
        Schema::create('adjustment_inventories', function (Blueprint $table) {
            $table->id();
            $table->enum('tipo', ['Entrada','Salida'])->default('Entrada');
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->date('fecha');
            $table->string('entidad', 8, 2);
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('descripcion');
            $table->decimal('monto', 8, 2);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('adjustment_inventories');
    }
};
