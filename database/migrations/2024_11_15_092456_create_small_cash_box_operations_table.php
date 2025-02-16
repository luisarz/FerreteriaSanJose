<?php

use App\Models\Employee;
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
        Schema::create('small_cash_box_operations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cash_box_open_id')->constrained('cash_box_opens')->cascadeOnDelete();
            $table->foreignId('employ_id')->constrained('employees')->cascadeOnDelete();
            $table->enum('operation', ['Ingreso', 'Egreso']);
            $table->decimal('amount', 10, 2);
            $table->string('concept');
            $table->json('voucher')->nullable();
            $table->boolean('status')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('small_cash_box_operations');
    }
    public function employee()
    {
        return $this->belongsTo(Employee::class);

    }
};
