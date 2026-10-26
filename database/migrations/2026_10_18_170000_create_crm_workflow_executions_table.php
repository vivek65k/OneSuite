<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_workflow_executions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('workflow_id')->constrained('crm_workflows')->cascadeOnDelete();
            $table->string('status')->default('success'); // success, failed
            $table->string('trigger_type')->default('manual'); // manual, retry
            $table->string('context_type')->nullable();
            $table->unsignedBigInteger('context_id')->nullable();
            $table->string('context_label')->nullable();
            $table->json('steps_log')->nullable();
            $table->text('error_message')->nullable();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('finished_at')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'workflow_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_workflow_executions');
    }
};
