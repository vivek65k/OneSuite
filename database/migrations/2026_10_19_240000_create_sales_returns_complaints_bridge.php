<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bridges the Sales side (crm_sales_orders) to the two systems that
     * already own returns and complaints: Inventory's customer returns
     * (physical stock-side return/receive) and CRM's cases (service-side
     * complaint tracking, SLA, escalation). Neither is duplicated here.
     */
    public function up(): void
    {
        Schema::table('inv_customer_returns', function (Blueprint $table) {
            $table->foreignId('sales_order_id')->nullable()->after('warehouse_id')->constrained('crm_sales_orders')->nullOnDelete();
            $table->foreignId('replacement_order_id')->nullable()->after('sales_order_id')->constrained('crm_sales_orders')->nullOnDelete();
        });

        Schema::table('crm_cases', function (Blueprint $table) {
            $table->foreignId('sales_order_id')->nullable()->after('contact_id')->constrained('crm_sales_orders')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('crm_cases', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sales_order_id');
        });

        Schema::table('inv_customer_returns', function (Blueprint $table) {
            $table->dropConstrainedForeignId('replacement_order_id');
            $table->dropConstrainedForeignId('sales_order_id');
        });
    }
};
