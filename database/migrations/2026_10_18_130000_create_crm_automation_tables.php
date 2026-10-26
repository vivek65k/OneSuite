<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_automation_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('category'); // assignment, approval, escalation, follow_up, trigger
            $table->string('name');
            $table->string('entity')->nullable(); // lead, case, opportunity
            $table->string('trigger_event')->nullable();
            $table->string('condition_field')->nullable();
            $table->string('condition_operator')->nullable();
            $table->string('condition_value')->nullable();
            $table->string('action_type')->nullable();
            $table->foreignId('action_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('run_count')->default(0);
            $table->dateTime('last_run_at')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'category']);
        });

        Schema::create('crm_workflows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('trigger_event')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('crm_workflow_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_id')->constrained('crm_workflows')->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('step_type'); // send_email, create_task, update_field, wait, send_notification
            $table->string('summary')->nullable();
            $table->json('config')->nullable();
            $table->timestamps();
        });

        Schema::create('crm_email_sequences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('crm_email_sequence_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sequence_id')->constrained('crm_email_sequences')->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->unsignedInteger('delay_days')->default(1);
            $table->string('subject');
            $table->text('body')->nullable();
            $table->timestamps();
        });

        Schema::create('crm_email_sequence_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sequence_id')->constrained('crm_email_sequences')->cascadeOnDelete();
            $table->foreignId('lead_id')->nullable()->constrained('crm_leads')->nullOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained('crm_contacts')->nullOnDelete();
            $table->unsignedInteger('current_step')->default(0);
            $table->string('status')->default('active'); // active, completed, stopped
            $table->dateTime('next_step_due_at')->nullable();
            $table->dateTime('enrolled_at')->nullable();
            $table->timestamps();
        });

        Schema::create('crm_scheduled_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('job_type'); // recalculate_campaign_counts, close_stale_leads, sync_case_sla
            $table->string('frequency')->default('daily');
            $table->boolean('is_active')->default(true);
            $table->dateTime('last_run_at')->nullable();
            $table->dateTime('next_run_at')->nullable();
            $table->text('last_result')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_scheduled_jobs');
        Schema::dropIfExists('crm_email_sequence_enrollments');
        Schema::dropIfExists('crm_email_sequence_steps');
        Schema::dropIfExists('crm_email_sequences');
        Schema::dropIfExists('crm_workflow_steps');
        Schema::dropIfExists('crm_workflows');
        Schema::dropIfExists('crm_automation_rules');
    }
};
