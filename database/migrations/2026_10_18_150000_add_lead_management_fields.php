<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('crm_leads', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->after('company_id')->constrained()->nullOnDelete();
            $table->string('budget')->nullable()->after('notes');
            $table->boolean('has_need')->nullable()->after('budget');
            $table->boolean('has_authority')->nullable()->after('has_need');
            $table->string('timeline')->nullable()->after('has_authority');
            $table->boolean('is_recycled')->default(false)->after('timeline');
            $table->dateTime('recycled_at')->nullable()->after('is_recycled');
            $table->text('recycle_reason')->nullable()->after('recycled_at');
        });
    }

    public function down(): void
    {
        Schema::table('crm_leads', function (Blueprint $table) {
            $table->dropConstrainedForeignId('branch_id');
            $table->dropColumn(['budget', 'has_need', 'has_authority', 'timeline', 'is_recycled', 'recycled_at', 'recycle_reason']);
        });
    }
};
