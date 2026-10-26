<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inv_warehouses', function (Blueprint $table) {
            $table->foreignId('manager_employee_id')->nullable()->after('branch_id')->constrained('employees')->nullOnDelete();
            $table->string('operating_hours')->nullable()->after('address');
            $table->string('type')->default('storage')->after('operating_hours'); // storage, production, transit, returns
            $table->string('picking_strategy')->default('fifo')->after('type'); // fifo, fefo, nearest_bin, batch, zone
        });

        // Zone -> Aisle -> Rack -> Shelf -> Bin, self-referencing so one
        // table covers the whole hierarchy instead of five parallel ones.
        Schema::create('inv_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained('inv_warehouses')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('inv_locations')->nullOnDelete();
            $table->string('level'); // zone, aisle, rack, shelf, bin
            $table->string('code');
            $table->string('full_code')->nullable();
            $table->decimal('max_weight', 12, 2)->nullable();
            $table->decimal('max_volume', 12, 2)->nullable();
            $table->string('storage_type')->nullable(); // ambient, cold, hazardous
            $table->boolean('is_blocked')->default(false);
            $table->timestamps();
        });

        Schema::table('inv_warehouses', function (Blueprint $table) {
            $table->foreignId('default_receiving_zone_id')->nullable()->after('picking_strategy')->constrained('inv_locations')->nullOnDelete();
            $table->foreignId('default_dispatch_zone_id')->nullable()->after('default_receiving_zone_id')->constrained('inv_locations')->nullOnDelete();
        });

        Schema::create('inv_putaway_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained('inv_warehouses')->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('inv_categories')->nullOnDelete();
            $table->string('strategy')->default('flexible'); // fixed, flexible
            $table->foreignId('fixed_location_id')->nullable()->constrained('inv_locations')->nullOnDelete();
            $table->boolean('is_hazardous')->default(false);
            $table->boolean('is_temp_controlled')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('inv_putaway_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained('inv_warehouses')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('inv_items')->cascadeOnDelete();
            $table->decimal('quantity', 14, 2);
            $table->foreignId('suggested_location_id')->nullable()->constrained('inv_locations')->nullOnDelete();
            $table->foreignId('confirmed_location_id')->nullable()->constrained('inv_locations')->nullOnDelete();
            $table->string('status')->default('pending'); // pending, completed, exception
            $table->string('source_reference')->nullable();
            $table->text('exception_note')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('inv_warehouse_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('transfer_number')->unique();
            $table->foreignId('source_warehouse_id')->constrained('inv_warehouses')->cascadeOnDelete();
            $table->foreignId('dest_warehouse_id')->constrained('inv_warehouses')->cascadeOnDelete();
            $table->string('status')->default('requested'); // requested, approved, picked, dispatched, in_transit, received, cancelled
            $table->foreignId('requested_by_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('approved_by_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->dateTime('requested_at')->nullable();
            $table->dateTime('dispatched_at')->nullable();
            $table->dateTime('received_at')->nullable();
            $table->timestamps();
        });

        Schema::create('inv_warehouse_transfer_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transfer_id')->constrained('inv_warehouse_transfers')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('inv_items')->cascadeOnDelete();
            $table->decimal('requested_qty', 14, 2)->default(0);
            $table->decimal('dispatched_qty', 14, 2)->nullable();
            $table->decimal('received_qty', 14, 2)->nullable();
            $table->text('discrepancy_notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inv_warehouse_transfer_lines');
        Schema::dropIfExists('inv_warehouse_transfers');
        Schema::dropIfExists('inv_putaway_tasks');
        Schema::dropIfExists('inv_putaway_rules');

        Schema::table('inv_warehouses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('default_dispatch_zone_id');
            $table->dropConstrainedForeignId('default_receiving_zone_id');
        });

        Schema::dropIfExists('inv_locations');

        Schema::table('inv_warehouses', function (Blueprint $table) {
            $table->dropColumn(['picking_strategy', 'type', 'operating_hours']);
            $table->dropConstrainedForeignId('manager_employee_id');
        });
    }
};
