<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('crm_accounts', 'branch_id')) {
            Schema::table('crm_accounts', function (Blueprint $table) {
                $table->foreignId('branch_id')->nullable()->after('company_id')->constrained()->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('crm_opportunities', 'branch_id')) {
            Schema::table('crm_opportunities', function (Blueprint $table) {
                $table->foreignId('branch_id')->nullable()->after('company_id')->constrained()->nullOnDelete();
                $table->foreignId('team_id')->nullable()->after('branch_id')->constrained('crm_teams')->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('crm_cases', 'branch_id')) {
            Schema::table('crm_cases', function (Blueprint $table) {
                $table->foreignId('branch_id')->nullable()->after('company_id')->constrained()->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('crm_campaigns', 'branch_id')) {
            Schema::table('crm_campaigns', function (Blueprint $table) {
                $table->foreignId('branch_id')->nullable()->after('company_id')->constrained()->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('crm_leads', 'team_id')) {
            Schema::table('crm_leads', function (Blueprint $table) {
                $table->foreignId('team_id')->nullable()->after('branch_id')->constrained('crm_teams')->nullOnDelete();
            });
        }

        // A pipeline (sales stage set) is shared across all branches by
        // default (branch_id null). Set it to scope that stage to one
        // branch instead, for organizations that want per-branch pipelines.
        if (! Schema::hasColumn('crm_sales_stages', 'branch_id')) {
            Schema::table('crm_sales_stages', function (Blueprint $table) {
                $table->foreignId('branch_id')->nullable()->after('company_id')->constrained()->nullOnDelete();
            });
        }

        // Accounts explicitly shared with employees or teams beyond the
        // owner - the "Shared" scope from the CRM data model.
        if (! Schema::hasTable('crm_account_shares')) {
            Schema::create('crm_account_shares', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                $table->foreignId('account_id')->constrained('crm_accounts')->cascadeOnDelete();
                $table->foreignId('employee_id')->nullable()->constrained('employees')->cascadeOnDelete();
                $table->foreignId('team_id')->nullable()->constrained('crm_teams')->cascadeOnDelete();
                $table->string('access_level')->default('view'); // view, edit
                $table->foreignId('shared_by_employee_id')->nullable()->constrained('employees')->nullOnDelete();
                $table->timestamps();
            });
        }

        // crm_settings becomes branch-aware: one row per (company, null)
        // is the company-wide default, one row per (company, branch_id)
        // overrides it for that branch.
        if (! Schema::hasColumn('crm_settings', 'branch_id')) {
            Schema::table('crm_settings', function (Blueprint $table) {
                $table->foreignId('branch_id')->nullable()->after('company_id')->constrained()->nullOnDelete();
                $table->string('pipeline_mode')->default('shared')->after('default_sla_policy_id'); // shared, per_branch
                $table->unique(['company_id', 'branch_id']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('crm_settings', 'branch_id')) {
            Schema::table('crm_settings', function (Blueprint $table) {
                $table->dropUnique(['company_id', 'branch_id']);
                $table->dropColumn('pipeline_mode');
                $table->dropConstrainedForeignId('branch_id');
            });
        }

        Schema::dropIfExists('crm_account_shares');

        if (Schema::hasColumn('crm_sales_stages', 'branch_id')) {
            Schema::table('crm_sales_stages', function (Blueprint $table) {
                $table->dropConstrainedForeignId('branch_id');
            });
        }

        if (Schema::hasColumn('crm_leads', 'team_id')) {
            Schema::table('crm_leads', function (Blueprint $table) {
                $table->dropConstrainedForeignId('team_id');
            });
        }

        if (Schema::hasColumn('crm_campaigns', 'branch_id')) {
            Schema::table('crm_campaigns', function (Blueprint $table) {
                $table->dropConstrainedForeignId('branch_id');
            });
        }

        if (Schema::hasColumn('crm_cases', 'branch_id')) {
            Schema::table('crm_cases', function (Blueprint $table) {
                $table->dropConstrainedForeignId('branch_id');
            });
        }

        if (Schema::hasColumn('crm_opportunities', 'team_id')) {
            Schema::table('crm_opportunities', function (Blueprint $table) {
                $table->dropConstrainedForeignId('team_id');
                $table->dropConstrainedForeignId('branch_id');
            });
        }

        if (Schema::hasColumn('crm_accounts', 'branch_id')) {
            Schema::table('crm_accounts', function (Blueprint $table) {
                $table->dropConstrainedForeignId('branch_id');
            });
        }
    }
};
