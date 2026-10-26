<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('type')->default('email');
            $table->string('status')->default('draft');
            $table->string('subject')->nullable();
            $table->text('message_body')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->decimal('budget', 14, 2)->default(0);
            $table->decimal('actual_cost', 14, 2)->default(0);
            $table->foreignId('owner_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->unsignedInteger('sent_count')->default(0);
            $table->unsignedInteger('delivered_count')->default(0);
            $table->unsignedInteger('opened_count')->default(0);
            $table->unsignedInteger('clicked_count')->default(0);
            $table->unsignedInteger('responded_count')->default(0);
            $table->text('description')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'type', 'status']);
        });

        Schema::create('crm_campaign_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('campaign_id')->constrained('crm_campaigns')->cascadeOnDelete();
            $table->string('member_type');
            $table->unsignedBigInteger('member_id');
            $table->string('status')->default('added');
            $table->timestamp('added_at')->nullable();
            $table->timestamps();
            $table->unique(['campaign_id', 'member_type', 'member_id']);
        });

        Schema::create('crm_web_forms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('campaign_id')->nullable()->constrained('crm_campaigns')->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->json('fields_json')->nullable();
            $table->string('redirect_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('submissions_count')->default(0);
            $table->timestamps();
        });

        Schema::create('crm_web_form_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('web_form_id')->constrained('crm_web_forms')->cascadeOnDelete();
            $table->json('data');
            $table->foreignId('lead_id')->nullable()->constrained('crm_leads')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('crm_landing_pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('campaign_id')->nullable()->constrained('crm_campaigns')->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('headline')->nullable();
            $table->longText('content')->nullable();
            $table->string('status')->default('draft');
            $table->unsignedInteger('views_count')->default(0);
            $table->unsignedInteger('conversions_count')->default(0);
            $table->timestamps();
        });

        Schema::table('crm_leads', function (Blueprint $table) {
            $table->foreignId('campaign_id')->nullable()->after('lead_source_id')->constrained('crm_campaigns')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('crm_leads', function (Blueprint $table) {
            $table->dropConstrainedForeignId('campaign_id');
        });

        Schema::dropIfExists('crm_landing_pages');
        Schema::dropIfExists('crm_web_form_submissions');
        Schema::dropIfExists('crm_web_forms');
        Schema::dropIfExists('crm_campaign_members');
        Schema::dropIfExists('crm_campaigns');
    }
};
