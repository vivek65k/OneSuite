<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inv_expected_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained('inv_warehouses')->cascadeOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained('inv_suppliers')->nullOnDelete();
            $table->string('reference_number')->unique();
            $table->string('po_reference')->nullable();
            $table->date('expected_date')->nullable();
            $table->string('vehicle_number')->nullable();
            $table->string('tracking_number')->nullable();
            $table->boolean('is_asn')->default(false);
            $table->string('status')->default('pending'); // pending, received, cancelled
            $table->timestamps();
        });

        Schema::create('inv_expected_receipt_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('receipt_id')->constrained('inv_expected_receipts')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('inv_items')->cascadeOnDelete();
            $table->decimal('expected_qty', 14, 2);
            $table->timestamps();
        });

        Schema::create('inv_grns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained('inv_warehouses')->cascadeOnDelete();
            $table->foreignId('expected_receipt_id')->nullable()->constrained('inv_expected_receipts')->nullOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained('inv_suppliers')->nullOnDelete();
            $table->string('grn_number')->unique();
            $table->string('status')->default('draft'); // draft, posted
            $table->foreignId('posted_by_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->dateTime('posted_at')->nullable();
            $table->timestamps();
        });

        Schema::create('inv_grn_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grn_id')->constrained('inv_grns')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('inv_items')->cascadeOnDelete();
            $table->decimal('received_qty', 14, 2)->default(0);
            $table->decimal('damaged_qty', 14, 2)->default(0);
            $table->string('batch_number')->nullable();
            $table->date('expiry_date')->nullable();
            $table->boolean('inspection_required')->default(false);
            $table->string('inspection_status')->default('not_required'); // not_required, pending, passed, failed
            $table->timestamps();
        });

        // Stock held pending quality release - excluded from available on-hand
        // in inv_stock until an inspection passes it through.
        Schema::create('inv_quarantine_stock', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained('inv_warehouses')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('inv_items')->cascadeOnDelete();
            $table->foreignId('grn_line_id')->constrained('inv_grn_lines')->cascadeOnDelete();
            $table->decimal('quantity', 14, 2);
            $table->timestamps();
        });

        Schema::create('inv_quality_inspections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('grn_line_id')->constrained('inv_grn_lines')->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained('inv_warehouses')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('inv_items')->cascadeOnDelete();
            $table->text('checklist_notes')->nullable();
            $table->unsignedInteger('sample_size')->nullable();
            $table->string('status')->default('pending'); // pending, completed
            $table->string('result')->nullable(); // pass, fail, partial
            $table->string('defect_reason')->nullable();
            $table->decimal('accepted_qty', 14, 2)->nullable();
            $table->decimal('rejected_qty', 14, 2)->nullable();
            $table->dateTime('inspected_at')->nullable();
            $table->timestamps();
        });

        Schema::create('inv_supplier_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained('inv_suppliers')->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained('inv_warehouses')->cascadeOnDelete();
            $table->string('return_number')->unique();
            $table->string('reason')->nullable();
            $table->string('status')->default('draft'); // draft, approved, dispatched
            $table->string('credit_note_reference')->nullable();
            $table->string('tracking_number')->nullable();
            $table->dateTime('dispatched_at')->nullable();
            $table->timestamps();
        });

        Schema::create('inv_supplier_return_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('return_id')->constrained('inv_supplier_returns')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('inv_items')->cascadeOnDelete();
            $table->decimal('quantity', 14, 2);
            $table->string('batch_number')->nullable();
            $table->string('serial_number')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inv_supplier_return_lines');
        Schema::dropIfExists('inv_supplier_returns');
        Schema::dropIfExists('inv_quality_inspections');
        Schema::dropIfExists('inv_quarantine_stock');
        Schema::dropIfExists('inv_grn_lines');
        Schema::dropIfExists('inv_grns');
        Schema::dropIfExists('inv_expected_receipt_lines');
        Schema::dropIfExists('inv_expected_receipts');
    }
};
