<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_sla_policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('priority');
            $table->unsignedInteger('response_hours')->default(4);
            $table->unsignedInteger('resolution_hours')->default(24);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('crm_cases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('case_number')->unique();
            $table->string('type')->default('case');
            $table->string('subject');
            $table->text('description')->nullable();
            $table->string('status')->default('open');
            $table->string('priority')->default('medium');
            $table->string('source')->nullable();
            $table->foreignId('account_id')->nullable()->constrained('crm_accounts')->nullOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained('crm_contacts')->nullOnDelete();
            $table->foreignId('assigned_to_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('sla_policy_id')->nullable()->constrained('crm_sla_policies')->nullOnDelete();
            $table->dateTime('response_due_at')->nullable();
            $table->dateTime('resolution_due_at')->nullable();
            $table->dateTime('first_responded_at')->nullable();
            $table->dateTime('resolved_at')->nullable();
            $table->boolean('is_escalated')->default(false);
            $table->foreignId('escalated_to_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->dateTime('escalated_at')->nullable();
            $table->text('escalation_reason')->nullable();
            $table->unsignedTinyInteger('csat_rating')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'type', 'status']);
        });

        Schema::create('crm_knowledge_articles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('category')->nullable();
            $table->longText('body')->nullable();
            $table->boolean('is_published')->default(false);
            $table->unsignedInteger('views_count')->default(0);
            $table->timestamps();
        });

        Schema::create('crm_customer_feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->nullable()->constrained('crm_accounts')->nullOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained('crm_contacts')->nullOnDelete();
            $table->foreignId('case_id')->nullable()->constrained('crm_cases')->nullOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->string('source')->nullable();
            $table->text('comment')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_customer_feedback');
        Schema::dropIfExists('crm_knowledge_articles');
        Schema::dropIfExists('crm_cases');
        Schema::dropIfExists('crm_sla_policies');
    }
};
