<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_documents', function (Blueprint $table) {
            $table->foreignId('company_id')->after('id')->constrained()->cascadeOnDelete();
            $table->date('expiry_date')->nullable()->after('file_path');
            $table->index(['company_id', 'category']);
        });
    }

    public function down(): void
    {
        Schema::table('employee_documents', function (Blueprint $table) {
            $table->dropIndex(['company_id', 'category']);
            $table->dropConstrainedForeignId('company_id');
            $table->dropColumn('expiry_date');
        });
    }
};
