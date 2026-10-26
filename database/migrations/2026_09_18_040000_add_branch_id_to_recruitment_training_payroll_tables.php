<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * These tables were flagged during the branch-scoping audit as having no
     * branch_id column at all, blocking proper scoping of Recruitment,
     * Training, and Payroll Run screens. Same backfill approach as the
     * earlier HRM settings migration: existing rows attach to their
     * company's first branch (alphabetically) so nothing disappears.
     */
    protected const SIMPLE_TABLES = ['candidates', 'courses', 'training_sessions', 'resume_bank_entries', 'payroll_runs'];

    public function up(): void
    {
        foreach (self::SIMPLE_TABLES as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->foreignId('branch_id')->nullable()->after('company_id')->constrained()->cascadeOnDelete();
            });
        }

        Schema::table('payroll_runs', function (Blueprint $table) {
            // New index first - see the note in add_branch_id_to_hrm_settings_tables.
            $table->unique(['company_id', 'branch_id', 'month', 'year']);
            $table->dropUnique(['company_id', 'month', 'year']);
        });

        $firstBranchByCompany = DB::table('branches')
            ->orderBy('company_id')
            ->orderBy('name')
            ->get(['id', 'company_id'])
            ->unique('company_id')
            ->pluck('id', 'company_id');

        foreach (self::SIMPLE_TABLES as $tableName) {
            foreach ($firstBranchByCompany as $companyId => $branchId) {
                DB::table($tableName)
                    ->where('company_id', $companyId)
                    ->whereNull('branch_id')
                    ->update(['branch_id' => $branchId]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('payroll_runs', function (Blueprint $table) {
            $table->dropUnique(['company_id', 'branch_id', 'month', 'year']);
            $table->unique(['company_id', 'month', 'year']);
        });

        foreach (self::SIMPLE_TABLES as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropConstrainedForeignId('branch_id');
            });
        }
    }
};
