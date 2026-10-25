<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * بند خطة الصيانة أصبح «المنتج + تاريخ الاستبدال» فقط. الدورية بالأشهر لم تعد تُطلب،
 * لكن العمود يبقى (اختياريًا) حتى لا تضيع قيم الخطط والعقود المسجلة قبل التغيير.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['maintenance_plan_items', 'sales_contract_maintenance_items'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->unsignedSmallInteger('interval_months')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        foreach (['maintenance_plan_items', 'sales_contract_maintenance_items'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->unsignedSmallInteger('interval_months')->nullable(false)->default(1)->change();
            });
        }
    }
};
