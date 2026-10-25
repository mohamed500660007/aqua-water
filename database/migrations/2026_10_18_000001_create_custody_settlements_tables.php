<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // تصحيح رصيد عهدة موظف بلا طرف مخزني مقابل — الأثر الفعلي يُسجَّل في employee_stock_movements
        Schema::create('custody_settlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('type', 20);
            $table->string('status', 20);
            $table->date('settlement_date');
            $table->string('reason', 150);
            $table->string('note', 500)->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'settlement_date']);
            $table->index('status');
        });

        Schema::create('custody_settlement_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('custody_settlement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('quantity');
            // تُملأ عند الترحيل فقط
            $table->integer('balance_before')->nullable();
            $table->integer('balance_after')->nullable();
            $table->timestamps();

            $table->unique(['custody_settlement_id', 'product_id']);
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('custody_settlement_items');
        Schema::dropIfExists('custody_settlements');
    }
};
