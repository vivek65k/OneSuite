<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('sku')->nullable();
            $table->text('description')->nullable();
            $table->decimal('unit_price', 14, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('crm_price_books', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('currency', 10)->default('INR');
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['company_id', 'name']);
        });

        Schema::create('crm_price_book_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('price_book_id')->constrained('crm_price_books')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('crm_products')->cascadeOnDelete();
            $table->decimal('price', 14, 2);
            $table->timestamps();
            $table->unique(['price_book_id', 'product_id']);
        });

        Schema::create('crm_quotations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('quotation_number');
            $table->foreignId('account_id')->nullable()->constrained('crm_accounts')->nullOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained('crm_contacts')->nullOnDelete();
            $table->foreignId('opportunity_id')->nullable()->constrained('crm_opportunities')->nullOnDelete();
            $table->foreignId('price_book_id')->nullable()->constrained('crm_price_books')->nullOnDelete();
            $table->string('status')->default('draft');
            $table->date('valid_until')->nullable();
            $table->foreignId('owner_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->decimal('subtotal', 14, 2)->default(0);
            $table->unsignedTinyInteger('discount_percent')->default(0);
            $table->decimal('discount_amount', 14, 2)->default(0);
            $table->decimal('tax_percent', 5, 2)->default(0);
            $table->decimal('total', 14, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['company_id', 'quotation_number']);
            $table->index(['company_id', 'status']);
        });

        Schema::create('crm_quotation_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_id')->constrained('crm_quotations')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('crm_products')->nullOnDelete();
            $table->string('description');
            $table->decimal('quantity', 10, 2)->default(1);
            $table->decimal('unit_price', 14, 2)->default(0);
            $table->unsignedTinyInteger('discount_percent')->default(0);
            $table->decimal('line_total', 14, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('crm_discount_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('quotation_id')->constrained('crm_quotations')->cascadeOnDelete();
            $table->unsignedTinyInteger('requested_discount_percent');
            $table->foreignId('requested_by_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('approver_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->string('status')->default('pending');
            $table->text('reason')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();
        });

        Schema::create('crm_proposals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('quotation_id')->nullable()->constrained('crm_quotations')->nullOnDelete();
            $table->foreignId('account_id')->nullable()->constrained('crm_accounts')->nullOnDelete();
            $table->string('title');
            $table->longText('content')->nullable();
            $table->string('status')->default('draft');
            $table->foreignId('owner_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });

        Schema::create('crm_sales_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('order_number');
            $table->foreignId('quotation_id')->nullable()->constrained('crm_quotations')->nullOnDelete();
            $table->foreignId('account_id')->nullable()->constrained('crm_accounts')->nullOnDelete();
            $table->string('status')->default('pending');
            $table->date('order_date')->nullable();
            $table->decimal('total', 14, 2)->default(0);
            $table->foreignId('owner_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->timestamps();
            $table->unique(['company_id', 'order_number']);
        });

        Schema::create('crm_sales_order_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_order_id')->constrained('crm_sales_orders')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('crm_products')->nullOnDelete();
            $table->string('description');
            $table->decimal('quantity', 10, 2)->default(1);
            $table->decimal('unit_price', 14, 2)->default(0);
            $table->decimal('line_total', 14, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('crm_contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('contract_number');
            $table->string('title');
            $table->foreignId('account_id')->nullable()->constrained('crm_accounts')->nullOnDelete();
            $table->foreignId('sales_order_id')->nullable()->constrained('crm_sales_orders')->nullOnDelete();
            $table->string('status')->default('draft');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->decimal('value', 14, 2)->default(0);
            $table->foreignId('owner_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->boolean('auto_renew')->default(false);
            $table->foreignId('renewed_from_contract_id')->nullable()->constrained('crm_contracts')->nullOnDelete();
            $table->timestamps();
            $table->unique(['company_id', 'contract_number']);
        });

        Schema::create('crm_esignature_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('signable_type');
            $table->unsignedBigInteger('signable_id');
            $table->string('signer_name');
            $table->string('signer_email');
            $table->string('status')->default('pending');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->timestamps();
            $table->index(['signable_type', 'signable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_esignature_requests');
        Schema::dropIfExists('crm_contracts');
        Schema::dropIfExists('crm_sales_order_lines');
        Schema::dropIfExists('crm_sales_orders');
        Schema::dropIfExists('crm_proposals');
        Schema::dropIfExists('crm_discount_approvals');
        Schema::dropIfExists('crm_quotation_lines');
        Schema::dropIfExists('crm_quotations');
        Schema::dropIfExists('crm_price_book_entries');
        Schema::dropIfExists('crm_price_books');
        Schema::dropIfExists('crm_products');
    }
};
