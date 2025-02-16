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
        Schema::create('transfers', function (Blueprint $table) {
            $table->id();
            $table->integer('transfer_number')->default(0)->nullable();
            $table->foreignId('wherehouse_from')->constrained('branches');
            $table->foreignId('user_send')->constrained('employees');
            $table->foreignId('wherehouse_to')->constrained('branches');
            $table->foreignId('user_recive')->nullable()->constrained('employees');
            $table->dateTime('transfer_date');
            $table->dateTime('received_date')->nullable();
            $table->decimal('total', 10, 2)->default(0);
            $table->string('status_send')->default('pendiente');
            $table->string('status_received')->default('pendiente')->nullable();
            $table->softDeletes();
            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transfers');
    }
};
