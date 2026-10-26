<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_policy_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('exclude_weekends')->default(true);
            $table->boolean('exclude_holidays')->default(true);
            $table->boolean('sandwich_rule')->default(false);
            $table->boolean('allow_half_day')->default(true);
            $table->unsignedSmallInteger('min_notice_days')->default(0);
            $table->unsignedSmallInteger('max_backdated_days')->nullable();
            $table->unsignedSmallInteger('max_consecutive_days')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'branch_id']);
        });

        Schema::table('leave_requests', function (Blueprint $table) {
            // 'first' / 'second' half of a single day; null = full day(s).
            $table->string('half_day', 10)->nullable()->after('days');
        });
    }

    public function down(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->dropColumn('half_day');
        });
        Schema::dropIfExists('leave_policy_settings');
    }
};
