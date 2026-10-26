<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('travel_settlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('travel_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->decimal('total_booking_cost', 12, 2)->default(0);
            $table->decimal('total_expense_amount', 12, 2)->default(0);
            $table->decimal('reimbursement_amount', 12, 2)->default(0);
            $table->string('status')->default('pending');
            $table->date('settled_date')->nullable();
            $table->foreignId('settled_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->unique('travel_request_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('travel_settlements');
    }
};
