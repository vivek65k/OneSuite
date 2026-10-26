<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Turns this from a single-company app into a real multi-tenant one:
     * every user now belongs to exactly one client (company_id), except a
     * small set of platform-level accounts (is_platform_admin) who manage
     * the client roster itself and don't belong to any one client.
     * Company::current() resolves from the logged-in user's own
     * company_id from here on, instead of always returning the one row
     * that used to be the only company in the system.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            $table->boolean('is_platform_admin')->default(false)->after('is_super_admin');
        });

        // Every existing user belonged to the one company that used to be
        // the only row - keep them exactly where they already were.
        $firstCompanyId = DB::table('companies')->orderBy('id')->value('id');

        if ($firstCompanyId) {
            DB::table('users')->whereNull('company_id')->update(['company_id' => $firstCompanyId]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
            $table->dropColumn('is_platform_admin');
        });
    }
};
