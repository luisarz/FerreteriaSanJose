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
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_id')->constrained('providers');
            $table->foreignId('employee_id')->constrained('employees');
            $table->foreignId('wherehouse_id')->constrained('branches');
            $table->date('purchase_date');
            $table->enum('document_type',['Electrónico','Físico']);
            $table->string('document_number');
            $table->enum('pruchase_condition',['Contado','Crédito']);
            $table->integer('credit_days')->nullable();
            $table->enum('status',['Procesando','Finalizado','Anulado'])->default('Procesando');
            $table->boolean('have_perception')->default(false);
            $table->decimal('net_value',10,2)->default(0);
            $table->decimal('taxe_value',10,2)->default(0);
            $table->decimal('perception_value' ,10,2)->default(0);
            $table->decimal('purchase_total',10,2)->default(0);
            $table->boolean('paid')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
};
