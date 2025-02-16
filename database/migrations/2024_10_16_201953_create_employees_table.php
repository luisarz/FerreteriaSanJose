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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('lastname');
            $table->string('email')->unique()->nullable();
            $table->string('phone');
            $table->string('address');
            $table->json('photo')->nullable();
            $table->date('birthdate')->nullable();
            $table->enum('gender', ['M', 'F'])->default('M');
            $table->enum('marital_status', ['Soltero/a', 'Casado/a', 'Divorciado/a', 'Viudo/a'])->default('Soltero/a');
            $table->string('marital_name')->nullable();
            $table->string('marital_phone')->nullable();
            $table->string('dui')->unique()->nullable();
            $table->string('nit')->unique()->nullable();
            $table->foreignId('department_id')->constrained('departamentos');
            $table->foreignId('distrito_id')->constrained('distritos');//Municipoio
            $table->foreignId('municipalitie_id')->constrained('municipalities');//Distrito
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('job_title_id')->constrained('job_titles')->cascadeOnDelete();
            $table->boolean('is_comisioned')->default(true);
            $table->decimal('comision', 10, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
