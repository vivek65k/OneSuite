<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A client whose mail provider has no sign-in option connects by SMTP
     * instead: that row has no OAuth tokens, and keeps its host / port /
     * username / password in "settings" (encrypted as a whole).
     */
    public function up(): void
    {
        Schema::table('company_mail_accounts', function (Blueprint $table) {
            $table->text('access_token')->nullable()->change();
            $table->text('refresh_token')->nullable()->change();
            $table->text('settings')->nullable()->after('token_expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('company_mail_accounts', function (Blueprint $table) {
            $table->dropColumn('settings');
        });
    }
};
