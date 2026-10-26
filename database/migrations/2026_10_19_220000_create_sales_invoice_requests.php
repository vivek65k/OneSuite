<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * There is no Finance app yet in OneSuite, so this is deliberately
     * scoped to what Sales actually owns per the module spec: raising
     * the request to invoice an order and tracking payment status back
     * for the sales team to see - not an official tax invoice, ledger
     * entry or accounting record. invoice_reference is a free-text field
     * for whatever number Finance eventually assigns once that app
     * exists; nothing here posts to any accounting record.
     */
    public function up(): void
    {
        Schema::create('sales_invoice_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sales_order_id')->constrained('crm_sales_orders')->cascadeOnDelete();
            $table->foreignId('contract_id')->nullable()->constrained('crm_contracts')->nullOnDelete();
            $table->string('status')->default('requested'); // requested, invoiced, partially_paid, paid, cancelled
            $table->decimal('requested_amount', 14, 2);
            $table->decimal('amount_paid', 14, 2)->default(0);
            $table->string('invoice_reference')->nullable();
            $table->date('due_date')->nullable();
            $table->foreignId('requested_by_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_invoice_requests');
    }
};
