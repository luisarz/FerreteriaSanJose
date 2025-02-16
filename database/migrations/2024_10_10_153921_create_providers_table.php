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
        Schema::create('providers', function (Blueprint $table) {
            $table->id();
            $table->string('legal_name');
            $table->string('comercial_name');
            $table->foreignId('country_id')->constrained('countries')->cascadeOnDelete();
            $table->foreignId('department_id')->constrained('departamentos');
            $table->foreignId('municipility_id')->constrained('distritos');
            $table->foreignId('distrito_id')->constrained('municipalities');//Los distritos se gurdan en municipalities
            $table->string('direction')->nullable();
            $table->string('phone_one')->nullable();
            $table->string('phone_two')->nullable();
            $table->string('email')->nullable();
            $table->string('nrc')->nullable();
            $table->string('nit')->nullable();
            $table->foreignId('economic_activity_id')->constrained('economic_activities');
            $table->enum('condition_payment', ['Contado', 'Credito'])->nullable();
            $table->integer('credit_days')->nullable();
            $table->decimal('credit_limit', 10, 2)->nullable();
            $table->decimal('balance', 10, 2)->nullable();
            $table->enum('provider_type', ['Pequeño', 'Grande', 'Mediano','Micro'])->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('contact_seller')->nullable();
            $table->string('phone_seller')->nullable();
            $table->string('email_seller')->nullable();
            $table->date('last_purchase')->nullable();
            $table->integer('purchase_decimals')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('providers');
    }
};
