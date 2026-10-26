<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A newly-invited user (platform-created client admin, or later a
     * client's own "add team member") gets an unusable random password
     * until they set their own via the emailed signed link - this column
     * is null until that happens, both so the accept-invitation page can
     * tell a fresh link from an already-used one, and so lists can show
     * "Invited" vs "Active" without inferring it from the password itself.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('invitation_accepted_at')->nullable()->after('email_verified_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('invitation_accepted_at');
        });
    }
};
