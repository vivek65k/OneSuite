<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inv_items', function (Blueprint $table) {
            $table->string('costing_method')->default('moving_avg')->after('cost_price'); // moving_avg, fifo
            $table->string('abc_class')->nullable()->after('costing_method'); // A, B, C
            $table->string('xyz_class')->nullable()->after('abc_class'); // X, Y, Z
        });

        Schema::table('inv_batches', function (Blueprint $table) {
            $table->decimal('unit_cost', 14, 2)->nullable()->after('quantity');
            $table->string('status')->default('active')->after('unit_cost'); // active, disposed
        });

        Schema::create('inv_reorder_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained('inv_warehouses')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('inv_items')->cascadeOnDelete();
            $table->decimal('reorder_point', 14, 2);
            $table->decimal('target_stock_level', 14, 2);
            $table->unsignedInteger('lead_time_days')->default(0);
            $table->decimal('safety_stock', 14, 2)->default(0);
            $table->boolean('auto_create_po')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('inv_purchase_suggestions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained('inv_warehouses')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('inv_items')->cascadeOnDelete();
            $table->foreignId('reorder_rule_id')->nullable()->constrained('inv_reorder_rules')->nullOnDelete();
            $table->decimal('on_hand_qty', 14, 2);
            $table->decimal('reserved_qty', 14, 2);
            $table->decimal('available_qty', 14, 2);
            $table->decimal('incoming_qty', 14, 2)->default(0);
            $table->decimal('suggested_qty', 14, 2);
            $table->string('status')->default('suggested'); // suggested, approved, dismissed
            $table->foreignId('approved_by_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->dateTime('approved_at')->nullable();
            $table->timestamps();
        });

        Schema::create('inv_demand_forecasts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained('inv_warehouses')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('inv_items')->cascadeOnDelete();
            $table->string('period_label'); // e.g. "Next 30 days"
            $table->decimal('historical_avg_qty', 14, 2);
            $table->decimal('forecast_qty', 14, 2);
            $table->string('method')->default('moving_average');
            $table->dateTime('generated_at');
            $table->timestamps();
        });

        Schema::create('inv_batch_disposals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('batch_id')->constrained('inv_batches')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('inv_items')->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained('inv_warehouses')->cascadeOnDelete();
            $table->decimal('quantity', 14, 2);
            $table->string('reason')->nullable();
            $table->string('status')->default('pending_approval'); // pending_approval, approved, rejected
            $table->foreignId('requested_by_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('approved_by_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->dateTime('approved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inv_batch_disposals');
        Schema::dropIfExists('inv_demand_forecasts');
        Schema::dropIfExists('inv_purchase_suggestions');
        Schema::dropIfExists('inv_reorder_rules');

        Schema::table('inv_batches', function (Blueprint $table) {
            $table->dropColumn(['unit_cost', 'status']);
        });

        Schema::table('inv_items', function (Blueprint $table) {
            $table->dropColumn(['costing_method', 'abc_class', 'xyz_class']);
        });
    }
};
