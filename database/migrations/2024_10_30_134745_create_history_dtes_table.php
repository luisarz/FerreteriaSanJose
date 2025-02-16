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
        Schema::create('history_dtes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_invoice_id')->nullable()->constrained('sales')->cascadeOnDelete();
            $table->string('version');
            $table->string('ambiente');
            $table->string('versionApp');
            $table->string('estado')->nullable();
            $table->string('codigoGeneracion')->nullable();
            $table->string('selloRecibido')->nullable();
            $table->dateTime('fhProcesamiento')->nullable();
            $table->string('clasificaMsg')->nullable();
            $table->string('codigoMsg')->nullable();
            $table->string('descripcionMsg')->nullable();
            $table->json('observaciones')->nullable();
            $table->json('dte')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('history_dtes');
    }
};
