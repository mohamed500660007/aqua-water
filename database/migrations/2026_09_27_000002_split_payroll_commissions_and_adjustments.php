<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('installation_commission', 12, 2)->default(0)->after('device_commission');
            $table->decimal('marketing_commission', 12, 2)->default(0)->after('installation_commission');
        });

        Schema::table('payrolls', function (Blueprint $table) {
            $table->unsignedInteger('installed_device_count')->default(0)->after('device_commission');
            $table->decimal('installation_commission', 12, 2)->default(0)->after('installed_device_count');
            $table->unsignedInteger('marketed_device_count')->default(0)->after('installation_commission');
            $table->decimal('marketing_commission', 12, 2)->default(0)->after('marketed_device_count');
            $table->decimal('regular_deduction', 12, 2)->default(0)->after('adjustment');
            $table->string('deduction_reason', 500)->nullable()->after('regular_deduction');
            $table->decimal('attendance_deduction', 12, 2)->default(0)->after('deduction_reason');
            $table->decimal('bonus', 12, 2)->default(0)->after('attendance_deduction');
        });

        DB::table('users')->update([
            'installation_commission' => DB::raw('device_commission'),
        ]);
        DB::table('payrolls')->update([
            'installed_device_count' => DB::raw('device_count'),
            'installation_commission' => DB::raw('device_commission'),
            'regular_deduction' => DB::raw('CASE WHEN adjustment < 0 THEN -adjustment ELSE 0 END'),
            'bonus' => DB::raw('CASE WHEN adjustment > 0 THEN adjustment ELSE 0 END'),
        ]);
    }

    public function down(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropColumn([
                'installed_device_count', 'installation_commission', 'marketed_device_count',
                'marketing_commission', 'regular_deduction', 'deduction_reason',
                'attendance_deduction', 'bonus',
            ]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['installation_commission', 'marketing_commission']);
        });
    }
};
