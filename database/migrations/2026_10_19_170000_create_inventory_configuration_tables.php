<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inv_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->unique()->constrained()->cascadeOnDelete();
            $table->boolean('allow_negative_stock')->default(false);
            $table->boolean('allow_backorder')->default(false);
            $table->boolean('require_batch_number_validation')->default(false);
            $table->boolean('require_serial_number_validation')->default(false);
            $table->string('default_costing_method')->default('moving_avg');
            $table->decimal('landed_cost_overhead_percent', 5, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('inv_approval_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('action_type'); // adjustment, transfer, disposal, customer_return
            $table->decimal('threshold_qty', 14, 2)->nullable();
            $table->decimal('threshold_value', 14, 2)->nullable();
            $table->string('approver_role')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('inv_numbering_series', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('document_type'); // grn, expected_receipt, transfer, adjustment, ...
            $table->string('prefix');
            $table->unsignedInteger('next_number')->default(1);
            $table->unsignedTinyInteger('padding_digits')->default(5);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['company_id', 'document_type']);
        });

        Schema::create('inv_custom_field_definitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('entity_type')->default('item'); // item, grn, transfer, adjustment
            $table->string('field_key');
            $table->string('label');
            $table->string('field_type')->default('text'); // text, number, date, select
            $table->text('options')->nullable(); // comma-separated for select
            $table->boolean('is_required')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['company_id', 'entity_type', 'field_key'], 'inv_cfd_company_entity_key_unique');
        });

        Schema::create('inv_custom_field_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('field_id')->constrained('inv_custom_field_definitions')->cascadeOnDelete();
            $table->string('entity_type');
            $table->unsignedBigInteger('entity_id');
            $table->text('value')->nullable();
            $table->timestamps();
            $table->unique(['field_id', 'entity_type', 'entity_id']);
        });

        Schema::create('inv_integration_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('integration_key'); // sales, purchase, production, finance, barcode_scanner
            $table->boolean('is_enabled')->default(false);
            $table->string('endpoint_url')->nullable();
            $table->string('api_key')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['company_id', 'integration_key']);
        });

        Schema::create('inv_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->string('action'); // created, updated, deleted, approved, rejected
            $table->string('entity_type');
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->string('description');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inv_audit_logs');
        Schema::dropIfExists('inv_integration_settings');
        Schema::dropIfExists('inv_custom_field_values');
        Schema::dropIfExists('inv_custom_field_definitions');
        Schema::dropIfExists('inv_numbering_series');
        Schema::dropIfExists('inv_approval_rules');
        Schema::dropIfExists('inv_settings');
    }
};
