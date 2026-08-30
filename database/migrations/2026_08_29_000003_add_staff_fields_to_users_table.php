<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 20)->nullable()->unique()->after('email');
            $table->string('role', 50)->nullable()->after('phone');
            $table->string('department', 50)->nullable()->after('role');
            $table->string('branch', 50)->nullable()->after('department');
            $table->string('status', 20)->default('active')->after('branch');
            $table->softDeletes();

            $table->index('name');
            $table->index('status');
            $table->index(['role', 'department', 'branch']);
        });

        // لا يوجد تسجيل دخول في النظام حاليًا، فالحساب قد يُنشأ بدون كلمة مرور
        Schema::table('users', function (Blueprint $table) {
            $table->string('password')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role', 'department', 'branch']);
            $table->dropIndex(['status']);
            $table->dropIndex(['name']);
            $table->dropUnique(['phone']);
            $table->dropSoftDeletes();
            $table->dropColumn(['phone', 'role', 'department', 'branch', 'status']);
        });
    }
};
