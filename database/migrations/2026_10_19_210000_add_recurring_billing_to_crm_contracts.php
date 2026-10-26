<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('crm_contracts', function (Blueprint $table) {
            $table->string('billing_frequency')->default('one_time')->after('value'); // one_time, monthly, quarterly, annually
            $table->date('next_billing_date')->nullable()->after('billing_frequency');
        });
    }

    public function down(): void
    {
        Schema::table('crm_contracts', function (Blueprint $table) {
            $table->dropColumn(['billing_frequency', 'next_billing_date']);
        });
    }
};
