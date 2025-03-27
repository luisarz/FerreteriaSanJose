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
        Schema::create('contingencies', function (Blueprint $table) {
            $table->bigInteger('id')->primary();
            $table->unsignedBigInteger('warehouse_id');
            $table->bigInteger('warehouse_id')->references('id')->on('branches');//Sucursal
            $table->string('uuid_hacienda');
            $table->datetimes('start_date');
            $table->datetimes('end_date');
            $table->unsignedBigInteger('contingency_types_id');
            $table->bigInteger('contingency_types_id')->references('id')->on('contingency_types');//Tipo de contingencia
            $table->string('continvengy_motivation');
            $table->datetimes('end_date');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contingencies');
    }
};
