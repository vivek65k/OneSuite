<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('crm_custom_reports', function (Blueprint $table) {
            $table->string('report_type')->default('table')->after('source'); // table, pivot, chart
            $table->json('config')->nullable()->after('report_type');
        });
    }

    public function down(): void
    {
        Schema::table('crm_custom_reports', function (Blueprint $table) {
            $table->dropColumn(['report_type', 'config']);
        });
    }
};
