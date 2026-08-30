<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // الرصيد الحالي — قيمة مشتقة من الحركات وتُحدَّث معها داخل نفس المعاملة
        Schema::create('stock_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->integer('quantity')->default(0);
            $table->timestamps();

            $table->unique(['product_id', 'warehouse_id']);
            $table->index('warehouse_id');
        });

        // سجل الحركات — المصدر الوحيد للحقيقة في المخزون
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20);
            $table->integer('quantity');
            $table->integer('balance_after');
            $table->nullableMorphs('reference');
            $table->string('note', 255)->nullable();
            $table->date('moved_at');
            $table->timestamps();

            $table->index(['product_id', 'warehouse_id']);
            $table->index('moved_at');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('stock_balances');
    }
};
