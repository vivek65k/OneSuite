<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Reserved quantity is tracked separately from on-hand quantity:
        // reservations reduce AVAILABLE stock, never inv_stock.quantity itself.
        Schema::table('inv_stock', function (Blueprint $table) {
            $table->decimal('reserved_quantity', 14, 2)->default(0)->after('quantity');
        });

        Schema::create('inv_stock_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained('inv_warehouses')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('inv_items')->cascadeOnDelete();
            $table->string('reservation_number')->unique();
            $table->string('reference')->nullable(); // e.g. sales order / customer reference
            $table->decimal('quantity', 14, 2);
            $table->string('status')->default('reserved'); // reserved, released, fulfilled
            $table->dateTime('released_at')->nullable();
            $table->timestamps();
        });

        Schema::create('inv_pick_lists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained('inv_warehouses')->cascadeOnDelete();
            $table->foreignId('picker_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->string('pick_list_number')->unique();
            $table->string('strategy')->nullable(); // fifo, fefo, nearest_bin, batch, zone
            $table->string('status')->default('pending'); // pending, picking, picked, cancelled
            $table->timestamps();
        });

        Schema::create('inv_pick_list_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pick_list_id')->constrained('inv_pick_lists')->cascadeOnDelete();
            $table->foreignId('reservation_id')->nullable()->constrained('inv_stock_reservations')->nullOnDelete();
            $table->foreignId('item_id')->constrained('inv_items')->cascadeOnDelete();
            $table->decimal('requested_qty', 14, 2);
            $table->decimal('picked_qty', 14, 2)->default(0);
            $table->string('status')->default('pending'); // pending, picked
            $table->timestamps();
        });

        Schema::create('inv_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained('inv_warehouses')->cascadeOnDelete();
            $table->foreignId('pick_list_id')->nullable()->constrained('inv_pick_lists')->nullOnDelete();
            $table->string('package_number')->unique();
            $table->decimal('weight', 10, 2)->nullable();
            $table->string('box_type')->nullable();
            $table->string('status')->default('packed'); // packed, dispatched
            $table->timestamps();
        });

        Schema::create('inv_package_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('package_id')->constrained('inv_packages')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('inv_items')->cascadeOnDelete();
            $table->decimal('quantity', 14, 2);
            $table->timestamps();
        });

        // Dispatch is the configured stock-reducing transaction; delivery
        // confirmation that follows only updates status/POD fields.
        Schema::create('inv_dispatches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained('inv_warehouses')->cascadeOnDelete();
            $table->foreignId('package_id')->constrained('inv_packages')->cascadeOnDelete();
            $table->string('dispatch_number')->unique();
            $table->string('carrier')->nullable();
            $table->string('tracking_number')->nullable();
            $table->string('status')->default('dispatched'); // dispatched, delivered
            $table->dateTime('dispatched_at')->nullable();
            $table->dateTime('delivered_at')->nullable();
            $table->string('received_by')->nullable();
            $table->string('signature_name')->nullable();
            $table->timestamps();
        });

        Schema::create('inv_customer_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained('inv_warehouses')->cascadeOnDelete();
            $table->foreignId('dispatch_id')->nullable()->constrained('inv_dispatches')->nullOnDelete();
            $table->string('return_number')->unique();
            $table->string('reason')->nullable();
            $table->string('status')->default('draft'); // draft, approved, received
            $table->dateTime('received_at')->nullable();
            $table->timestamps();
        });

        Schema::create('inv_customer_return_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('return_id')->constrained('inv_customer_returns')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('inv_items')->cascadeOnDelete();
            $table->decimal('quantity', 14, 2);
            $table->string('batch_number')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inv_customer_return_lines');
        Schema::dropIfExists('inv_customer_returns');
        Schema::dropIfExists('inv_dispatches');
        Schema::dropIfExists('inv_package_lines');
        Schema::dropIfExists('inv_packages');
        Schema::dropIfExists('inv_pick_list_lines');
        Schema::dropIfExists('inv_pick_lists');
        Schema::dropIfExists('inv_stock_reservations');

        Schema::table('inv_stock', function (Blueprint $table) {
            $table->dropColumn('reserved_quantity');
        });
    }
};
