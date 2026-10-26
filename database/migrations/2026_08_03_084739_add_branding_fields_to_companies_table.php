<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->text('footer_text')->nullable()->after('logo');
            $table->string('login_title')->nullable()->after('footer_text');
            $table->string('login_subtitle')->nullable()->after('login_title');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['footer_text', 'login_title', 'login_subtitle']);
        });
    }
};
