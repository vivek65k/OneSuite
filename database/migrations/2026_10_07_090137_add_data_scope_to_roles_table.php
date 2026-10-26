<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Whose data a role's permissions reach: 'all' (everyone in the
     * branch), 'team' (the holder and everyone below them in the
     * reporting chain) or 'own' (the holder's own records only) - see
     * App\Support\DataScope. Existing roles default to 'all', which is
     * exactly how every role behaved before this column existed.
     */
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->string('data_scope', 10)->default('all')->after('guard_name');
        });
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn('data_scope');
        });
    }
};
