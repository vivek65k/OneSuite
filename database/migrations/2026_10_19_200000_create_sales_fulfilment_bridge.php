<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bridges CRM's own product catalog (crm_products, used on quotation
     * and order lines) to Inventory's shared item master (inv_items), so
     * confirming a Sales Order can reserve real Inventory stock instead
     * of the two catalogs staying disconnected.
     */
    public function up(): void
    {
        Schema::table('crm_products', function (Blueprint $table) {
            $table->foreignId('inventory_item_id')->nullable()->after('id')->constrained('inv_items')->nullOnDelete();
        });

        Schema::table('crm_sales_orders', function (Blueprint $table) {
            $table->foreignId('warehouse_id')->nullable()->after('account_id')->constrained('inv_warehouses')->nullOnDelete();
        });

        Schema::table('crm_sales_order_lines', function (Blueprint $table) {
            $table->foreignId('reservation_id')->nullable()->after('fulfilled_qty')->constrained('inv_stock_reservations')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('crm_sales_order_lines', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reservation_id');
        });

        Schema::table('crm_sales_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('warehouse_id');
        });

        Schema::table('crm_products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('inventory_item_id');
        });
    }
};
