<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('esi_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->unique()->constrained()->cascadeOnDelete();
            $table->decimal('employee_contribution_percent', 5, 2)->default(0.75);
            $table->decimal('employer_contribution_percent', 5, 2)->default(3.25);
            $table->decimal('wage_ceiling', 12, 2)->default(21000);
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('esi_settings');
    }
};
