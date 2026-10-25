<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * تبسيط ملف الصيانة:
 * - المواعيد تأتي من بنود خطة الصيانة المنسوخة على كل عقد (sales_contract_maintenance_items) بدل إعداد مراحل منفصل،
 *   فتُحذف candle_stages و candle_schedules.
 * - عقد الصيانة لم يعد رصيدًا يُستهلك زيارة بزيارة: يُسجَّل كعملية واحدة بكل الشمعات ويُقفل ملف العميل،
 *   فيُحذف maintenance_packages وتُربط العملية بعقد الصيانة في المبيعات مباشرة.
 * - كل بند عملية يحفظ موعد الشمعة السابق حتى يعيده إلغاء العملية كما كان.
 * - هدايا عقد الصيانة تُسجَّل في جدول الهدايا مربوطة بالعملية.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_contract_maintenance_items', function (Blueprint $table) {
            $table->date('last_replaced_at')->nullable()->after('replacement_date');
            $table->index('replacement_date');
        });

        Schema::table('maintenance_operations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('maintenance_package_id');
        });

        Schema::table('maintenance_operations', function (Blueprint $table) {
            // عقد الصيانة في المبيعات الذي سُجّل فيه المبلغ (كاش أو أقساط) — لا تتكرر المبالغ هنا
            $table->foreignId('maintenance_contract_id')->nullable()->after('sales_contract_id')
                ->constrained('sales_contracts')->nullOnDelete();
            $table->index(['sales_contract_id', 'type']);
        });

        Schema::table('maintenance_operation_items', function (Blueprint $table) {
            // في MySQL قد يعتمد المفتاح الأجنبي على الفهرس المركّب، فيُضاف فهرس مستقل قبل حذفه
            $table->index('maintenance_operation_id');
            $table->dropUnique('mo_items_operation_position_unique');
        });

        Schema::table('maintenance_operation_items', function (Blueprint $table) {
            $table->dropColumn('position');
            // بند الخطة يُعاد إنشاؤه عند تغيير خطة العقد، فيبقى سجل العملية بلا رابط
            // أسماء صريحة لأن التلقائية تتجاوز حد MySQL (64 حرفًا)
            $table->foreignId('sales_contract_maintenance_item_id')->nullable()->after('maintenance_operation_id')
                ->constrained('sales_contract_maintenance_items', 'id', 'mo_items_contract_candle_foreign')->nullOnDelete();
            $table->date('previous_replacement_date')->nullable();
            $table->date('previous_last_replaced_at')->nullable();
        });

        Schema::table('gifts', function (Blueprint $table) {
            $table->foreignId('maintenance_operation_id')->nullable()->after('customer_id')->constrained()->nullOnDelete();
        });

        Schema::dropIfExists('maintenance_packages');
        Schema::dropIfExists('candle_schedules');
        Schema::dropIfExists('candle_stages');
    }

    public function down(): void
    {
        Schema::create('candle_stages', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('position')->unique();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('interval_months');
            $table->timestamps();
        });

        Schema::create('candle_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_contract_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('position');
            $table->date('last_replaced_at')->nullable();
            $table->date('next_due_at');
            $table->timestamps();

            $table->unique(['sales_contract_id', 'position']);
            $table->index('next_due_at');
        });

        Schema::create('maintenance_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->foreignId('sales_contract_id')->constrained()->restrictOnDelete();
            $table->foreignId('maintenance_contract_id')->nullable()->constrained('sales_contracts')->nullOnDelete();
            $table->foreignId('technician_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('sales_rep_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedSmallInteger('candles');
            $table->unsignedSmallInteger('gift_candles')->default(0);
            $table->unsignedSmallInteger('used_candles')->default(0);
            $table->decimal('technician_commission', 12, 2)->default(0);
            $table->decimal('sales_rep_commission', 12, 2)->default(0);
            $table->string('status', 20);
            $table->date('started_at');
            $table->date('closed_at')->nullable();
            $table->string('note', 500)->nullable();
            $table->timestamps();

            $table->index(['customer_id', 'status']);
            $table->index('started_at');
        });

        Schema::table('gifts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('maintenance_operation_id');
        });

        Schema::table('maintenance_operation_items', function (Blueprint $table) {
            $table->dropForeign('mo_items_contract_candle_foreign');
            $table->dropColumn('sales_contract_maintenance_item_id');
            $table->dropColumn(['previous_replacement_date', 'previous_last_replaced_at']);
            $table->unsignedTinyInteger('position')->default(0);
        });

        Schema::table('maintenance_operation_items', function (Blueprint $table) {
            $table->unique(['maintenance_operation_id', 'position'], 'mo_items_operation_position_unique');
            $table->dropIndex(['maintenance_operation_id']);
        });

        Schema::table('maintenance_operations', function (Blueprint $table) {
            $table->dropIndex(['sales_contract_id', 'type']);
            $table->dropConstrainedForeignId('maintenance_contract_id');
            $table->foreignId('maintenance_package_id')->nullable()->after('sales_contract_id')->constrained()->restrictOnDelete();
        });

        Schema::table('sales_contract_maintenance_items', function (Blueprint $table) {
            $table->dropIndex(['replacement_date']);
            $table->dropColumn('last_replaced_at');
        });
    }
};
