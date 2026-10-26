<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The Sales app does not duplicate the customer or product master.
     * sales_customer_profiles extends crm_accounts (the shared customer
     * master) with commercial fields; pricing tables reference inv_items
     * (the shared product master) directly.
     */
    public function up(): void
    {
        Schema::create('sales_price_lists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('currency', 10)->default('INR');
            $table->boolean('is_default')->default(false);
            $table->date('valid_from')->nullable();
            $table->date('valid_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('sales_customer_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->unique()->constrained('crm_accounts')->cascadeOnDelete();
            $table->decimal('credit_limit', 14, 2)->default(0);
            $table->unsignedInteger('payment_terms_days')->default(0);
            $table->foreignId('price_list_id')->nullable()->constrained('sales_price_lists')->nullOnDelete();
            $table->foreignId('salesperson_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->string('status')->default('active'); // active, on_hold, blocked
            $table->decimal('outstanding_balance', 14, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('sales_price_list_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('price_list_id')->constrained('sales_price_lists')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('inv_items')->cascadeOnDelete();
            $table->decimal('unit_price', 14, 2);
            $table->decimal('min_qty', 14, 2)->nullable();
            $table->timestamps();
            $table->unique(['price_list_id', 'item_id', 'min_qty'], 'sales_pli_list_item_minqty_unique');
        });

        Schema::create('sales_customer_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('crm_accounts')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('inv_items')->cascadeOnDelete();
            $table->decimal('unit_price', 14, 2);
            $table->timestamps();
            $table->unique(['account_id', 'item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_customer_prices');
        Schema::dropIfExists('sales_price_list_items');
        Schema::dropIfExists('sales_customer_profiles');
        Schema::dropIfExists('sales_price_lists');
    }
};
