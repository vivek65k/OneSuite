<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_rule_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedInteger('late_grace_minutes')->default(0);
            $table->decimal('half_day_threshold_hours', 4, 2)->default(4);
            $table->decimal('full_day_threshold_hours', 4, 2)->default(8);
            $table->boolean('auto_mark_absent_if_no_checkin')->default(false);
            $table->decimal('weekend_overtime_multiplier', 4, 2)->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_rule_settings');
    }
};
