<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Enables Spatie's "teams" feature (see config/permission.php and
     * App\Support\CompanyTeamResolver) by adding the same company_id
     * column Spatie's own migration would generate if 'teams' had been
     * on from the start. Without this, "Role" is a single global list
     * shared by every client - one client editing a role's permissions
     * would silently change every other client's role of the same name.
     *
     * Not added to the pivots' primary keys (SQLite makes redefining an
     * existing composite primary key genuinely risky to do live) - Spatie
     * scopes its queries by the column value alone (see HasRoles::roles()
     * wherePivot() and Role's creating-event auto-stamp), which doesn't
     * require the column to be part of the key, just present and correct.
     */
    public function up(): void
    {
        // config/permission.php now has 'teams' => true, so on a fresh
        // database create_permission_tables already built these columns
        // (and the team-aware unique/primary keys) - nothing to add.
        if (Schema::hasColumn('roles', 'company_id')) {
            return;
        }

        Schema::table('roles', function (Blueprint $table) {
            $table->unsignedBigInteger('company_id')->nullable()->after('id');
            $table->index('company_id', 'roles_team_foreign_key_index');
        });

        Schema::table('model_has_roles', function (Blueprint $table) {
            $table->unsignedBigInteger('company_id')->nullable();
            $table->index('company_id', 'model_has_roles_team_foreign_key_index');
        });

        Schema::table('model_has_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('company_id')->nullable();
            $table->index('company_id', 'model_has_permissions_team_foreign_key_index');
        });

        // The one pre-existing company's roles/assignments stay exactly
        // where they already were.
        $firstCompanyId = DB::table('companies')->orderBy('id')->value('id');

        if ($firstCompanyId) {
            DB::table('roles')->whereNull('company_id')->update(['company_id' => $firstCompanyId]);
            DB::table('model_has_roles')->whereNull('company_id')->update(['company_id' => $firstCompanyId]);
            DB::table('model_has_permissions')->whereNull('company_id')->update(['company_id' => $firstCompanyId]);
        }

        Schema::table('roles', function (Blueprint $table) {
            $table->dropUnique(['name', 'guard_name']);
            $table->unique(['company_id', 'name', 'guard_name']);
        });
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropUnique(['company_id', 'name', 'guard_name']);
            $table->unique(['name', 'guard_name']);
            $table->dropColumn('company_id');
        });

        Schema::table('model_has_roles', function (Blueprint $table) {
            $table->dropColumn('company_id');
        });

        Schema::table('model_has_permissions', function (Blueprint $table) {
            $table->dropColumn('company_id');
        });
    }
};
