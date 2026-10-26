<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * app_access: which App\Support\AppLauncher app keys this user may
     * open (null = unrestricted, every app - keeps existing users working
     * unchanged and stays future-proof as new apps are added).
     * default_app: which of those apps they land on right after login,
     * instead of everyone always landing on the Settings dashboard.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->json('app_access')->nullable()->after('is_super_admin');
            $table->string('default_app', 50)->nullable()->after('app_access');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['app_access', 'default_app']);
        });
    }
};
