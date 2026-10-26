<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete()->unique();
            $table->string('default_currency')->default('USD');
            $table->unsignedTinyInteger('fiscal_year_start_month')->default(1);
            $table->string('business_hours_start')->default('09:00');
            $table->string('business_hours_end')->default('18:00');
            $table->foreignId('default_sla_policy_id')->nullable()->constrained('crm_sla_policies')->nullOnDelete();
            $table->boolean('auto_assign_leads')->default(false);
            $table->timestamps();
        });

        Schema::create('crm_custom_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('entity');
            $table->string('field_name');
            $table->string('field_label');
            $table->string('field_type')->default('text');
            $table->json('options')->nullable();
            $table->boolean('is_required')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['company_id', 'entity', 'field_name']);
        });

        Schema::create('crm_custom_objects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('label');
            $table->text('description')->nullable();
            $table->json('fields_json')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('crm_layouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('entity');
            $table->json('fields_json')->nullable();
            $table->timestamps();
            $table->unique(['company_id', 'entity']);
        });

        Schema::create('crm_teams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->foreignId('manager_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('crm_team_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained('crm_teams')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['team_id', 'employee_id']);
        });

        Schema::create('crm_territories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('team_id')->nullable()->constrained('crm_teams')->nullOnDelete();
            $table->string('name');
            $table->string('criteria')->nullable();
            $table->timestamps();
        });

        Schema::create('crm_quotas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('team_id')->nullable()->constrained('crm_teams')->nullOnDelete();
            $table->string('period')->default('month'); // month, quarter, year
            $table->date('period_start');
            $table->decimal('target_amount', 14, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('crm_integrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('type'); // email, sms, payment, calendar, webhook
            $table->json('config')->nullable();
            $table->boolean('is_connected')->default(false);
            $table->timestamps();
        });

        Schema::create('crm_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->json('permissions')->nullable();
            $table->timestamps();
        });

        Schema::create('crm_role_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained('crm_roles')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['role_id', 'employee_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_role_assignments');
        Schema::dropIfExists('crm_roles');
        Schema::dropIfExists('crm_integrations');
        Schema::dropIfExists('crm_quotas');
        Schema::dropIfExists('crm_territories');
        Schema::dropIfExists('crm_team_members');
        Schema::dropIfExists('crm_teams');
        Schema::dropIfExists('crm_layouts');
        Schema::dropIfExists('crm_custom_objects');
        Schema::dropIfExists('crm_custom_fields');
        Schema::dropIfExists('crm_settings');
    }
};
