<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ملف الصيانة وتتبع الشمعات:
 * - candle_stages: إعداد ثابت لمراحل الفلتر السبع (أي شمعة في كل مرحلة وكل كام شهر تتغير).
 * - candle_schedules: موعد كل مرحلة لكل جهاز مركّب (عقد بيع) — يُحسب من تاريخ التركيب أو آخر تغيير.
 * - maintenance_packages: عقد الصيانة الجملة كرصيد شمعات في حساب العميل يُستهلك مع كل زيارة.
 * - maintenance_operations / items: عمليات الصيانة الفعلية (قطاعي بسعر، أو من الباكيج بلا سعر).
 */
return new class extends Migration
{
    public function up(): void
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
            // عقد الصيانة الذي سُجّل فيه المبلغ (كاش أو أقساط) — المبالغ لا تُكرر هنا
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

        Schema::create('maintenance_operations', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20);
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->foreignId('sales_contract_id')->constrained()->restrictOnDelete();
            $table->foreignId('maintenance_package_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('technician_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('sales_rep_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('stock_source', 20);
            $table->foreignId('warehouse_id')->nullable()->constrained()->restrictOnDelete();
            $table->date('serviced_at');
            $table->decimal('total', 12, 2)->default(0);
            $table->decimal('technician_commission', 12, 2)->default(0);
            $table->decimal('sales_rep_commission', 12, 2)->default(0);
            $table->string('note', 500)->nullable();
            $table->timestamps();

            $table->index(['sales_contract_id', 'serviced_at']);
            $table->index('serviced_at');
            $table->index('technician_id');
            $table->index('sales_rep_id');
        });

        Schema::create('maintenance_operation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('maintenance_operation_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('position');
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->string('product_name', 150);
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->timestamps();

            $table->unique(['maintenance_operation_id', 'position']);
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_operation_items');
        Schema::dropIfExists('maintenance_operations');
        Schema::dropIfExists('maintenance_packages');
        Schema::dropIfExists('candle_schedules');
        Schema::dropIfExists('candle_stages');
    }
};
