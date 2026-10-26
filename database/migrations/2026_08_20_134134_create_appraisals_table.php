<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appraisals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('appraisal_cycle_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('draft');
            $table->unsignedTinyInteger('self_review_rating')->nullable();
            $table->text('self_review_comments')->nullable();
            $table->timestamp('self_review_submitted_at')->nullable();
            $table->unsignedTinyInteger('manager_review_rating')->nullable();
            $table->text('manager_review_comments')->nullable();
            $table->foreignId('manager_reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('manager_review_submitted_at')->nullable();
            $table->decimal('overall_rating', 3, 2)->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->unique(['employee_id', 'appraisal_cycle_id']);
            $table->index(['company_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appraisals');
    }
};
