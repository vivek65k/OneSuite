<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Multi-level approval along the reporting chain (App\Support\ApprovalFlow).
     *
     * approval_level_settings - how many levels of managers a request type
     * must pass at a client (no row = 1, i.e. one approval, as before).
     *
     * approval_steps - one row per level a request has passed (or the
     * level it was rejected at): who acted, and whether they did it for
     * their own level or on behalf of one below them.
     */
    public function up(): void
    {
        Schema::create('approval_level_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('request_type', 50);
            $table->unsignedTinyInteger('levels')->default(1);
            $table->timestamps();

            $table->unique(['company_id', 'request_type']);
        });

        Schema::create('approval_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->morphs('approvable');
            $table->unsignedTinyInteger('level');
            $table->string('status', 20);
            $table->foreignId('acted_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('on_behalf')->default(false);
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_steps');
        Schema::dropIfExists('approval_level_settings');
    }
};
