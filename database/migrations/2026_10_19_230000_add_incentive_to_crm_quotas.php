<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('crm_quotas', function (Blueprint $table) {
            $table->decimal('incentive_percent', 5, 2)->default(0)->after('target_amount');
        });
    }

    public function down(): void
    {
        Schema::table('crm_quotas', function (Blueprint $table) {
            $table->dropColumn('incentive_percent');
        });
    }
};
