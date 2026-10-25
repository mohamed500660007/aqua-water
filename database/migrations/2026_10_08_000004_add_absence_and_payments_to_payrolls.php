<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->unsignedSmallInteger('absence_days')->default(0)->after('attendance_deduction');
            $table->decimal('device_sales_total', 12, 2)->default(0)->after('marketing_commission');
        });

        Schema::create('payroll_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->date('paid_at');
            $table->string('note', 255)->nullable();
            $table->timestamps();

            $table->index('payroll_id');
            $table->index('paid_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_payments');

        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropColumn(['absence_days', 'device_sales_total']);
        });
    }
};
