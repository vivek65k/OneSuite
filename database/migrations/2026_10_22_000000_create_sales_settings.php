<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('discount_approval_threshold_percent')->default(15);
            $table->unsignedInteger('default_payment_terms_days')->default(30);
            $table->boolean('require_credit_check')->default(true);
            $table->boolean('block_order_over_credit_limit')->default(false);
            $table->foreignId('default_price_list_id')->nullable()->constrained('sales_price_lists')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_settings');
    }
};
