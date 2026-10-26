<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_slabs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('regime');
            $table->string('financial_year');
            $table->decimal('min_income', 14, 2);
            $table->decimal('max_income', 14, 2)->nullable();
            $table->decimal('tax_rate_percent', 5, 2);
            $table->timestamps();
            $table->index(['company_id', 'regime', 'financial_year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_slabs');
    }
};
