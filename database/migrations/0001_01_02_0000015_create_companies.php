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
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('nrc');
            $table->string('nit');
            $table->string('phone');
            $table->string('whatsapp');
            $table->string('email');
            $table->json('logo')->nullable();
            $table->foreignId('economic_activity_id')->constrained('economic_activities');
            $table->foreignId('country_id')->constrained('countries');
            $table->foreignId('departamento_id')->constrained('departamentos');
            $table->foreignId('distrito_id')->constrained('distritos');
            $table->string('address');
            $table->string('web');
            $table->foreignId('destination_envarioment')->nullable()->constrained('destination_enviroments');
            $table->string('api_key')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
