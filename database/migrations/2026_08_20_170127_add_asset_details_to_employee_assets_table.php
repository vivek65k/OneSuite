<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_assets', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('employee_id')->constrained()->cascadeOnDelete();
            $table->string('category')->default('other')->after('asset_name');
            $table->string('identifier')->nullable()->after('asset_tag');
            $table->string('brand_model')->nullable()->after('identifier');
            $table->string('condition_at_return')->nullable()->after('status');
            $table->text('remarks')->nullable()->after('condition_at_return');
        });

        DB::statement('
            UPDATE employee_assets
            SET company_id = (SELECT company_id FROM employees WHERE employees.id = employee_assets.employee_id)
            WHERE company_id IS NULL
        ');
    }

    public function down(): void
    {
        Schema::table('employee_assets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
            $table->dropColumn(['category', 'identifier', 'brand_model', 'condition_at_return', 'remarks']);
        });
    }
};
