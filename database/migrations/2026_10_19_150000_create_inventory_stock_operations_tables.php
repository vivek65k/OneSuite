<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Permanent, append-only transaction history for every stock
        // movement across the whole Inventory module.
        Schema::create('inv_stock_ledger', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained('inv_warehouses')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('inv_items')->cascadeOnDelete();
            $table->foreignId('location_id')->nullable()->constrained('inv_locations')->nullOnDelete();
            $table->string('batch_number')->nullable();
            $table->string('serial_number')->nullable();
            $table->string('transaction_type'); // opening, receipt, issue, transfer_out, transfer_in, adjustment, count_adjustment, return_in, return_out
            $table->decimal('quantity_before', 14, 2);
            $table->decimal('quantity_change', 14, 2);
            $table->decimal('quantity_after', 14, 2);
            $table->string('source_type'); // e.g. Grn, WarehouseTransfer, Dispatch, StockAdjustment, CountSheet
            $table->string('source_reference'); // e.g. GRN-2026-00001
            $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->string('reason')->nullable();
            $table->timestamps();
        });

        Schema::create('inv_stock_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained('inv_warehouses')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('inv_items')->cascadeOnDelete();
            $table->string('adjustment_number')->unique();
            $table->decimal('system_qty', 14, 2);
            $table->decimal('physical_qty', 14, 2);
            $table->decimal('adjustment_qty', 14, 2); // physical - system, can be negative
            $table->string('reason_code'); // shortage, excess, damage, expiry, loss
            $table->text('reason_notes')->nullable();
            $table->string('supporting_document')->nullable();
            $table->string('status')->default('pending_approval'); // pending_approval, approved, rejected
            $table->foreignId('requested_by_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('approved_by_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->dateTime('approved_at')->nullable();
            $table->string('rejected_reason')->nullable();
            $table->timestamps();
        });

        Schema::create('inv_replenishment_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained('inv_warehouses')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('inv_items')->cascadeOnDelete();
            $table->foreignId('bin_location_id')->constrained('inv_locations')->cascadeOnDelete();
            $table->foreignId('source_location_id')->nullable()->constrained('inv_locations')->nullOnDelete();
            $table->decimal('min_qty', 14, 2);
            $table->decimal('max_qty', 14, 2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('inv_replenishment_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained('inv_warehouses')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('inv_items')->cascadeOnDelete();
            $table->foreignId('replenishment_rule_id')->nullable()->constrained('inv_replenishment_rules')->nullOnDelete();
            $table->foreignId('from_location_id')->nullable()->constrained('inv_locations')->nullOnDelete();
            $table->foreignId('to_location_id')->nullable()->constrained('inv_locations')->nullOnDelete();
            $table->decimal('suggested_qty', 14, 2);
            $table->string('priority')->default('normal'); // low, normal, high
            $table->string('status')->default('pending'); // pending, completed, cancelled
            $table->dateTime('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('inv_count_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained('inv_warehouses')->cascadeOnDelete();
            $table->string('name');
            $table->string('type')->default('cycle'); // cycle, physical
            $table->string('frequency')->nullable(); // daily, weekly, monthly
            $table->string('abc_class')->nullable(); // A, B, C
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('inv_count_sheets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained('inv_warehouses')->cascadeOnDelete();
            $table->foreignId('schedule_id')->nullable()->constrained('inv_count_schedules')->nullOnDelete();
            $table->string('count_number')->unique();
            $table->string('type')->default('cycle'); // cycle, physical
            $table->boolean('blind_count')->default(true);
            $table->string('status')->default('counting'); // counting, variance_review, approved, cancelled
            $table->timestamps();
        });

        Schema::create('inv_count_sheet_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sheet_id')->constrained('inv_count_sheets')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('inv_items')->cascadeOnDelete();
            $table->foreignId('location_id')->nullable()->constrained('inv_locations')->nullOnDelete();
            $table->decimal('system_qty', 14, 2);
            $table->decimal('counted_qty', 14, 2)->nullable();
            $table->decimal('variance', 14, 2)->nullable();
            $table->string('status')->default('pending'); // pending, counted, recount_requested, approved
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inv_count_sheet_lines');
        Schema::dropIfExists('inv_count_sheets');
        Schema::dropIfExists('inv_count_schedules');
        Schema::dropIfExists('inv_replenishment_tasks');
        Schema::dropIfExists('inv_replenishment_rules');
        Schema::dropIfExists('inv_stock_adjustments');
        Schema::dropIfExists('inv_stock_ledger');
    }
};
