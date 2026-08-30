<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ما يحمله كل موظف من منتجات أخذها من المخزن الرئيسي
        Schema::create('employee_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->integer('quantity')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'product_id']);
            $table->index('product_id');
        });

        Schema::create('employee_stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20);
            $table->integer('quantity');
            $table->integer('balance_after');
            $table->nullableMorphs('reference');
            $table->string('note', 255)->nullable();
            $table->date('moved_at');
            $table->timestamps();

            $table->index(['user_id', 'product_id']);
            $table->index('moved_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_stock_movements');
        Schema::dropIfExists('employee_stocks');
    }
};
