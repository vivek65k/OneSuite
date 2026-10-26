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
        Schema::table('users', function (Blueprint $table) {
            $table->string('employee_code')->nullable()->unique()->after('id');
            $table->string('avatar')->nullable()->after('is_super_admin');
            $table->string('phone')->nullable()->after('avatar');
            $table->enum('gender', ['male', 'female', 'other'])->nullable()->after('phone');
            $table->date('date_of_birth')->nullable()->after('gender');
            $table->string('designation')->nullable()->after('date_of_birth');
            $table->string('department')->nullable()->after('designation');
            $table->date('joining_date')->nullable()->after('department');
            $table->boolean('is_active')->default(true)->after('joining_date');
            $table->string('address')->nullable()->after('is_active');
            $table->string('city')->nullable()->after('address');
            $table->string('state')->nullable()->after('city');
            $table->string('country')->nullable()->after('state');
            $table->string('postal_code')->nullable()->after('country');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'employee_code',
                'avatar',
                'phone',
                'gender',
                'date_of_birth',
                'designation',
                'department',
                'joining_date',
                'is_active',
                'address',
                'city',
                'state',
                'country',
                'postal_code',
            ]);
        });
    }
};
