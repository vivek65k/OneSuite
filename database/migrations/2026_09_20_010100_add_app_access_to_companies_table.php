<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Which OneSuite apps a client has been given, set per-client on the
     * new Clients (platform admin) screen - null means unrestricted
     * (every app), the same convention as users.app_access, so the one
     * pre-existing company keeps full access with no migration step.
     */
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->json('app_access')->nullable()->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('app_access');
        });
    }
};
