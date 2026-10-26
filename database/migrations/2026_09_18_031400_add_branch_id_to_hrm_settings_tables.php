<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * These "settings" tables were company-wide (shared across every branch)
     * until now - each one gets a branch_id so a branch-restricted admin
     * only ever sees and edits their own branch's policy configuration,
     * matching how every transactional HRM table already scopes by branch.
     * Existing rows backfill to their company's first branch so nothing
     * currently configured silently disappears.
     */
    protected const SIMPLE_TABLES = [
        'leave_types', 'shifts', 'overtime_rules', 'salary_components', 'expense_categories',
        'tax_slabs', 'document_templates', 'hr_policies',
    ];

    protected const CODE_UNIQUE_TABLES = ['leave_types' => 'code', 'shifts' => 'code', 'salary_components' => 'code'];

    protected const SINGLETON_TABLES = ['pf_settings', 'esi_settings', 'attendance_rule_settings'];

    public function up(): void
    {
        foreach (self::SIMPLE_TABLES as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->foreignId('branch_id')->nullable()->after('company_id')->constrained()->cascadeOnDelete();
            });
        }

        // MySQL: the old unique index is also what backs the company_id
        // foreign key, so it can only be dropped once another index leading
        // with company_id exists - add the new one first, then drop.
        foreach (self::CODE_UNIQUE_TABLES as $tableName => $codeColumn) {
            Schema::table($tableName, function (Blueprint $table) use ($codeColumn) {
                $table->unique(['company_id', 'branch_id', $codeColumn]);
                $table->dropUnique(['company_id', $codeColumn]);
            });
        }

        Schema::table('expense_categories', function (Blueprint $table) {
            $table->unique(['company_id', 'branch_id', 'name']);
            $table->dropUnique(['company_id', 'name']);
        });

        foreach (self::SINGLETON_TABLES as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->foreignId('branch_id')->nullable()->after('company_id')->constrained()->cascadeOnDelete();
                $table->unique(['company_id', 'branch_id']);
                $table->dropUnique(['company_id']);
            });
        }

        Schema::table('working_hours', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->after('company_id')->constrained()->cascadeOnDelete();
            $table->unique(['company_id', 'branch_id', 'day_of_week']);
            $table->dropUnique(['company_id', 'day_of_week']);
        });

        // Backfill: every row that predates branch scoping belongs to its
        // company's first branch (alphabetically), so it stays visible
        // instead of vanishing under the new branch filter.
        $firstBranchByCompany = DB::table('branches')
            ->orderBy('company_id')
            ->orderBy('name')
            ->get(['id', 'company_id'])
            ->unique('company_id')
            ->pluck('id', 'company_id');

        foreach (array_merge(self::SIMPLE_TABLES, self::SINGLETON_TABLES, ['working_hours']) as $tableName) {
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
        foreach (array_merge(self::SIMPLE_TABLES, self::SINGLETON_TABLES, ['working_hours']) as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (in_array($tableName, self::SINGLETON_TABLES, true)) {
                    $table->dropUnique(['company_id', 'branch_id']);
                    $table->unique('company_id');
                } elseif ($tableName === 'working_hours') {
                    $table->dropUnique(['company_id', 'branch_id', 'day_of_week']);
                    $table->unique(['company_id', 'day_of_week']);
                } elseif (array_key_exists($tableName, self::CODE_UNIQUE_TABLES)) {
                    $table->dropUnique(['company_id', 'branch_id', self::CODE_UNIQUE_TABLES[$tableName]]);
                    $table->unique(['company_id', self::CODE_UNIQUE_TABLES[$tableName]]);
                } elseif ($tableName === 'expense_categories') {
                    $table->dropUnique(['company_id', 'branch_id', 'name']);
                    $table->unique(['company_id', 'name']);
                }

                $table->dropConstrainedForeignId('branch_id');
            });
        }
    }
};
