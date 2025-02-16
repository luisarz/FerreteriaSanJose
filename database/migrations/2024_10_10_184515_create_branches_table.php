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
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stablisment_type_id')->constrained('stablishment_types');
            $table->string('name');
            $table->foreignId('company_id')->constrained();
            $table->string('nit');
            $table->string('nrc');
            $table->foreignId('departamento_id')->constrained('departamentos');
            $table->foreignId('distrito_id')->constrained('distritos');
            $table->string('address');
            $table->foreignId('economic_activity_id')->constrained('economic_activities');
            $table->string('phone');
            $table->string('email');
            $table->string('web');
            $table->integer('prices_by_products')->default(2);
            $table->json('logo')->nullable();
            $table->integer('print')->nullable()->default(1);
            $table->foreignId('destination')->constrained('destination_enviroments');
            $table->foreignId('contingency')->nullable()->constrained('contingency_types');
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
        Schema::dropIfExists('branches');
    }
};
