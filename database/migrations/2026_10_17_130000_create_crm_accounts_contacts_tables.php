<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_customer_segments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
            $table->unique(['company_id', 'name']);
        });

        Schema::create('crm_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('industry')->nullable();
            $table->string('website')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->foreignId('parent_account_id')->nullable()->constrained('crm_accounts')->nullOnDelete();
            $table->foreignId('owner_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('customer_segment_id')->nullable()->constrained('crm_customer_segments')->nullOnDelete();
            $table->timestamps();
            $table->index(['company_id', 'parent_account_id']);
        });

        Schema::table('crm_contacts', function (Blueprint $table) {
            $table->foreignId('account_id')->nullable()->after('company_id')->constrained('crm_accounts')->nullOnDelete();
            $table->string('job_title')->nullable()->after('name');
            $table->foreignId('owner_employee_id')->nullable()->after('phone')->constrained('employees')->nullOnDelete();
            $table->foreignId('customer_segment_id')->nullable()->after('owner_employee_id')->constrained('crm_customer_segments')->nullOnDelete();
        });

        Schema::create('crm_contact_relationships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_id')->constrained('crm_contacts')->cascadeOnDelete();
            $table->foreignId('related_contact_id')->constrained('crm_contacts')->cascadeOnDelete();
            $table->string('relationship_type');
            $table->timestamps();
            $table->unique(['contact_id', 'related_contact_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_contact_relationships');

        Schema::table('crm_contacts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('account_id');
            $table->dropConstrainedForeignId('owner_employee_id');
            $table->dropConstrainedForeignId('customer_segment_id');
            $table->dropColumn('job_title');
        });

        Schema::dropIfExists('crm_accounts');
        Schema::dropIfExists('crm_customer_segments');
    }
};
