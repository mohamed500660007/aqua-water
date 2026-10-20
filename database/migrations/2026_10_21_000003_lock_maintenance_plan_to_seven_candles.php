<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * خطة صيانة واحدة ثابتة: سبع شمعات بمواعيد مقفولة في الكود (MaintenancePlan::CANDLE_INTERVALS) تنطبق على كل الفلاتر،
 * فلا منتجات ولا بنود تُحفظ للخطة — يُحذف جدول بنودها.
 * الخطة الأقدم تبقى هي الخطة الوحيدة (أو تُنشأ إن لم توجد)، وبقية الخطط تُحذف حذفًا ناعمًا؛
 * العقود المرتبطة بها تحتفظ بنسختها الثابتة من الشمعات.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('maintenance_plan_items');

        $planId = DB::table('maintenance_plans')->whereNull('deleted_at')->orderBy('id')->value('id')
            ?? DB::table('maintenance_plans')->insertGetId([
                'name' => 'خطة الصيانة',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        DB::table('maintenance_plans')
            ->whereNull('deleted_at')
            ->where('id', '!=', $planId)
            ->update(['deleted_at' => now()]);
    }

    public function down(): void
    {
        Schema::create('maintenance_plan_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('maintenance_plan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('interval_months')->nullable();
            $table->date('replacement_date');
            $table->timestamps();

            $table->unique(['maintenance_plan_id', 'product_id']);
            $table->index('product_id');
        });
    }
};
