<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Extends the existing CRM-owned crm_quotations / crm_sales_orders -
     * the Sales app's Quotations & Orders screens operate on these same
     * tables rather than creating a second, duplicate set.
     */
    public function up(): void
    {
        Schema::create('crm_quotation_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('discount_percent')->default(0);
            $table->decimal('tax_percent', 5, 2)->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('created_by_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('crm_quotation_template_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('crm_quotation_templates')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('crm_products')->nullOnDelete();
            $table->string('description');
            $table->decimal('quantity', 14, 2)->default(1);
            $table->decimal('unit_price', 14, 2)->default(0);
            $table->timestamps();
        });

        // Append-only snapshot history - one row per saved change, never
        // edited or deleted, so "what did this quote look like on date X"
        // can always be answered.
        Schema::create('crm_quotation_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_id')->constrained('crm_quotations')->cascadeOnDelete();
            $table->unsignedInteger('version_number');
            $table->json('snapshot');
            $table->foreignId('changed_by_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });

        // Append-only order timeline - every status transition, amendment
        // and cancellation is recorded here for the Order Detail & Timeline
        // screen and the module's audit-trail requirement.
        Schema::create('crm_sales_order_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_order_id')->constrained('crm_sales_orders')->cascadeOnDelete();
            $table->string('event_type'); // status_change, amendment, cancellation, fulfilment
            $table->string('description');
            $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::table('crm_sales_order_lines', function (Blueprint $table) {
            $table->decimal('fulfilled_qty', 14, 2)->default(0)->after('quantity');
        });

        Schema::table('crm_sales_orders', function (Blueprint $table) {
            $table->string('cancelled_reason')->nullable()->after('status');
            $table->dateTime('cancelled_at')->nullable()->after('cancelled_reason');
            $table->dateTime('closed_at')->nullable()->after('cancelled_at');
        });
    }

    public function down(): void
    {
        Schema::table('crm_sales_orders', function (Blueprint $table) {
            $table->dropColumn(['cancelled_reason', 'cancelled_at', 'closed_at']);
        });

        Schema::table('crm_sales_order_lines', function (Blueprint $table) {
            $table->dropColumn('fulfilled_qty');
        });

        Schema::dropIfExists('crm_sales_order_events');
        Schema::dropIfExists('crm_quotation_versions');
        Schema::dropIfExists('crm_quotation_template_lines');
        Schema::dropIfExists('crm_quotation_templates');
    }
};
