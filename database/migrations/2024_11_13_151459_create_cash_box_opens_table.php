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
        Schema::create('cash_box_opens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cashbox_id')->constrained('cash_boxes');
            $table->foreignId('open_employee_id')->constrained('employees');
            $table->dateTime('opened_at');
            $table->decimal('open_amount', 10, 2)->default(0);
            $table->decimal('saled_amount', 10, 2)->nullable()->default(0);
            $table->decimal('ordered_amount', 10, 2)->nullable()->default(0);
            $table->decimal('out_cash_amount', 10, 2)->nullable()->default(0);
            $table->decimal('in_cash_amount', 10, 2)->nullable()->default(0);
            $table->decimal('closed_amount', 10, 2)->nullable()->default(0);
            $table->dateTime('closed_at')->nullable();
            $table->foreignId('close_employee_id')->nullable()->constrained('employees');
            $table->enum('status', ['open', 'closed'])->default('open');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_box_opens');
    }
};
