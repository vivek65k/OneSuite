<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('travel_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('travel_request_id')->constrained()->cascadeOnDelete();
            $table->string('booking_type')->default('flight');
            $table->string('provider')->nullable();
            $table->string('booking_reference')->nullable();
            $table->decimal('cost', 12, 2)->default(0);
            $table->date('travel_date');
            $table->text('details')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('travel_bookings');
    }
};
