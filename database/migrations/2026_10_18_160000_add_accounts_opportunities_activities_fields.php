<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('crm_accounts', function (Blueprint $table) {
            $table->text('billing_address')->nullable()->after('address');
            $table->text('shipping_address')->nullable()->after('billing_address');
        });

        Schema::table('crm_opportunities', function (Blueprint $table) {
            $table->text('risks_blockers')->nullable()->after('notes');
        });

        Schema::table('crm_activities', function (Blueprint $table) {
            $table->boolean('is_recurring')->default(false)->after('reminder_at');
            $table->string('recurrence_frequency')->nullable()->after('is_recurring'); // daily, weekly, monthly
            $table->foreignId('recurrence_parent_id')->nullable()->after('recurrence_frequency')->constrained('crm_activities')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('crm_activities', function (Blueprint $table) {
            $table->dropConstrainedForeignId('recurrence_parent_id');
            $table->dropColumn(['is_recurring', 'recurrence_frequency']);
        });

        Schema::table('crm_opportunities', function (Blueprint $table) {
            $table->dropColumn('risks_blockers');
        });

        Schema::table('crm_accounts', function (Blueprint $table) {
            $table->dropColumn(['billing_address', 'shipping_address']);
        });
    }
};
